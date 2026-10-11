"""
LeanOn-Bot Machine Learning Training Pipeline
Built with scikit-learn for student mental health NLP classification,
intent recognition, emotion detection, distress scoring, and strategy recommendation.
"""

import os
import json
import logging
from datetime import datetime
import joblib
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression, Ridge
from sklearn.pipeline import Pipeline
from sklearn.model_selection import cross_val_score, StratifiedKFold
from sklearn.metrics import classification_report, accuracy_score, f1_score

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("ml_trainer")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "data")
MODELS_DIR = os.path.join(BASE_DIR, "models")
os.makedirs(MODELS_DIR, exist_ok=True)
os.makedirs(DATA_DIR, exist_ok=True)


def load_training_data():
    """
    Loads seed data and merges with any available MySQL conversation data.
    """
    seed_path = os.path.join(DATA_DIR, "seed_conversations.json")
    records = []

    if os.path.exists(seed_path):
        with open(seed_path, "r", encoding="utf-8") as f:
            records = json.load(f)
            logger.info("Loaded %d seed conversation examples.", len(records))

    # Optional: fetch real messages from database if pymysql is available
    try:
        import pymysql
        from dotenv import load_dotenv

        backend_env = os.path.join(BASE_DIR, "..", "backend", ".env")
        if os.path.exists(backend_env):
            load_dotenv(backend_env)

        db_host = os.getenv("DB_HOST", "127.0.0.1")
        db_user = os.getenv("DB_USERNAME", "root")
        db_pass = os.getenv("DB_PASSWORD", "")
        db_name = os.getenv("DB_DATABASE", "leanon_bot")
        db_port = int(os.getenv("DB_PORT", "3306"))

        conn = pymysql.connect(
            host=db_host,
            user=db_user,
            password=db_pass,
            database=db_name,
            port=db_port,
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor
        )
        with conn.cursor() as cursor:
            # Ingest student messages with associated emotion logs or crisis flags
            sql = """
            SELECT cm.id, cm.message, cm.is_crisis, cm.is_fallback,
                   el.emotion, ca.severity
            FROM chat_messages cm
            LEFT JOIN emotion_logs el ON el.conversation_id = cm.conversation_id
            LEFT JOIN crisis_alerts ca ON ca.chat_message_id = cm.id
            WHERE LENGTH(cm.message) > 3
            ORDER BY cm.id DESC
            LIMIT 500
            """
            cursor.execute(sql)
            db_rows = cursor.fetchall()
            logger.info("Retrieved %d messages from MySQL.", len(db_rows))

            for row in db_rows:
                msg = row["message"].strip()
                if not msg:
                    continue

                is_crisis = bool(row.get("is_crisis"))
                is_fallback = bool(row.get("is_fallback"))
                db_emotion = row.get("emotion") or "neutral"
                severity = row.get("severity") or ("high" if is_crisis else "low")

                domain = "out_of_scope" if is_fallback else ("mental_health" if is_crisis else "academic_stress")
                intent = "crisis_expression" if is_crisis else ("other" if is_fallback else "venting")
                emotion = "hopeless" if is_crisis else (db_emotion if db_emotion in ["sad", "anxious", "stressed", "overwhelmed", "lonely", "angry", "positive", "hopeful"] else "stressed")
                tone = "serious" if is_crisis else "emotional"
                distress = 0.95 if is_crisis else (0.1 if is_fallback else 0.6)

                records.append({
                    "text": msg,
                    "domain": domain,
                    "intent": intent,
                    "emotion": emotion,
                    "tone": tone,
                    "distress_score": distress,
                    "severity": severity
                })
        conn.close()
    except Exception as e:
        logger.info("Database ingestion skipped or unavailable (%s). Using seed dataset.", e)

    return records


def train_models():
    """
    Fits scikit-learn models for domain, intent, emotion, tone, and distress scoring.
    Saves models with joblib and outputs metadata.
    """
    dataset = load_training_data()
    if not dataset:
        raise ValueError("No training data available to train ML models.")

    texts = [d["text"] for d in dataset]
    domains = [d.get("domain", "general_support") for d in dataset]
    # Binary mental health domain label: 1 for mental_health/academic_stress/general_support, 0 for out_of_scope
    is_mh = [0 if d.get("domain") == "out_of_scope" else 1 for d in dataset]
    intents = [d.get("intent", "venting") for d in dataset]
    emotions = [d.get("emotion", "stressed") for d in dataset]
    tones = [d.get("tone", "casual") for d in dataset]
    distress_scores = [float(d.get("distress_score", 0.5)) for d in dataset]

    logger.info("Training on %d total samples...", len(texts))

    metrics = {}

    # 1. Domain Relevance Classifier (Binary: In-Scope Mental Health/Student Support vs Out-of-Scope)
    domain_pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True, min_df=1)),
        ("clf", LogisticRegression(class_weight="balanced", max_iter=1000, random_state=42))
    ])
    domain_pipeline.fit(texts, is_mh)
    domain_preds = domain_pipeline.predict(texts)
    metrics["domain_accuracy"] = float(accuracy_score(is_mh, domain_preds))
    metrics["domain_f1"] = float(f1_score(is_mh, domain_preds, average="weighted"))

    # 2. Intent Classifier (Multinomial Logistic Regression)
    intent_pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True, min_df=1)),
        ("clf", LogisticRegression(class_weight="balanced", max_iter=1000, random_state=42))
    ])
    intent_pipeline.fit(texts, intents)
    intent_preds = intent_pipeline.predict(texts)
    metrics["intent_accuracy"] = float(accuracy_score(intents, intent_preds))
    metrics["intent_f1"] = float(f1_score(intents, intent_preds, average="weighted"))

    # 3. Emotion Classifier (8-class: sad, anxious, stressed, overwhelmed, lonely, angry, positive, hopeful)
    emotion_pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True, min_df=1)),
        ("clf", LogisticRegression(class_weight="balanced", max_iter=1000, random_state=42))
    ])
    emotion_pipeline.fit(texts, emotions)
    emotion_preds = emotion_pipeline.predict(texts)
    metrics["emotion_accuracy"] = float(accuracy_score(emotions, emotion_preds))
    metrics["emotion_f1"] = float(f1_score(emotions, emotion_preds, average="weighted"))

    # 4. Tone Classifier (casual, emotional, serious)
    tone_pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True, min_df=1)),
        ("clf", LogisticRegression(class_weight="balanced", max_iter=1000, random_state=42))
    ])
    tone_pipeline.fit(texts, tones)
    tone_preds = tone_pipeline.predict(texts)
    metrics["tone_accuracy"] = float(accuracy_score(tones, tone_preds))
    metrics["tone_f1"] = float(f1_score(tones, tone_preds, average="weighted"))

    # 5. Distress Scorer (Ridge Regressor clipped 0.0 - 1.0)
    distress_pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), sublinear_tf=True, min_df=1)),
        ("reg", Ridge(alpha=1.0, random_state=42))
    ])
    distress_pipeline.fit(texts, distress_scores)
    distress_preds = np.clip(distress_pipeline.predict(texts), 0.0, 1.0)
    metrics["distress_mae"] = float(np.mean(np.abs(np.array(distress_scores) - distress_preds)))

    # Save fitted model artifacts
    joblib.dump(domain_pipeline, os.path.join(MODELS_DIR, "domain_model.joblib"))
    joblib.dump(intent_pipeline, os.path.join(MODELS_DIR, "intent_model.joblib"))
    joblib.dump(emotion_pipeline, os.path.join(MODELS_DIR, "emotion_model.joblib"))
    joblib.dump(tone_pipeline, os.path.join(MODELS_DIR, "tone_model.joblib"))
    joblib.dump(distress_pipeline, os.path.join(MODELS_DIR, "distress_model.joblib"))

    # Save metadata
    metadata = {
        "version": "1.0.0",
        "trained_at": datetime.utcnow().isoformat() + "Z",
        "total_samples": len(texts),
        "intents": sorted(list(set(intents))),
        "emotions": sorted(list(set(emotions))),
        "tones": sorted(list(set(tones))),
        "metrics": metrics
    }
    with open(os.path.join(MODELS_DIR, "metadata.json"), "w", encoding="utf-8") as f:
        json.dump(metadata, f, indent=2)

    logger.info("Training complete! Metrics: %s", metrics)
    return metadata


if __name__ == "__main__":
    train_models()
