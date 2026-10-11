"""
LeanOn-Bot ML Predictor & Context Understanding Engine
Handles real-time inference using trained scikit-learn pipelines.
Provides standalone CLI and importable module interfaces.
"""

import os
import sys
import json
import logging
import joblib
import numpy as np

logging.basicConfig(level=logging.WARNING)
logger = logging.getLogger("ml_predictor")

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR = os.path.join(BASE_DIR, "models")


class MLPredictor:
    def __init__(self, models_dir=MODELS_DIR):
        self.models_dir = models_dir
        self.domain_model = None
        self.intent_model = None
        self.emotion_model = None
        self.tone_model = None
        self.distress_model = None
        self.metadata = {}
        self.load_models()

    def load_models(self):
        try:
            domain_path = os.path.join(self.models_dir, "domain_model.joblib")
            intent_path = os.path.join(self.models_dir, "intent_model.joblib")
            emotion_path = os.path.join(self.models_dir, "emotion_model.joblib")
            tone_path = os.path.join(self.models_dir, "tone_model.joblib")
            distress_path = os.path.join(self.models_dir, "distress_model.joblib")
            meta_path = os.path.join(self.models_dir, "metadata.json")

            if os.path.exists(domain_path):
                self.domain_model = joblib.load(domain_path)
            if os.path.exists(intent_path):
                self.intent_model = joblib.load(intent_path)
            if os.path.exists(emotion_path):
                self.emotion_model = joblib.load(emotion_path)
            if os.path.exists(tone_path):
                self.tone_model = joblib.load(tone_path)
            if os.path.exists(distress_path):
                self.distress_model = joblib.load(distress_path)
            if os.path.exists(meta_path):
                with open(meta_path, "r", encoding="utf-8") as f:
                    self.metadata = json.load(f)
        except Exception as e:
            logger.warning("Error loading models: %s", e)

    @property
    def is_ready(self):
        return (
            self.domain_model is not None
            and self.intent_model is not None
            and self.emotion_model is not None
        )

    def predict(self, message: str, history: list = None) -> dict:
        """
        Analyze student message and optional conversation history.
        """
        text = (message or "").strip()
        history = history or []

        # Default fallback response if models not loaded
        result = {
            "is_mental_health": True,
            "mental_health_confidence": 0.85,
            "intent": "venting",
            "intent_confidence": 0.80,
            "emotion": "stressed",
            "emotion_confidence": 0.80,
            "tone": "casual",
            "distress_score": 0.40,
            "suggested_severity": "low",
            "recommended_strategy": "EMPATHETIC_LISTENING",
            "strategy_prompt": "Listen warmly, validate the student's emotions, and offer gentle support.",
            "ml_status": "model_fallback"
        }

        if not text:
            return result

        if not self.is_ready:
            self.load_models()
            if not self.is_ready:
                return result

        try:
            # 1. Domain Relevance Prediction
            domain_prob = self.domain_model.predict_proba([text])[0]
            # class 1 corresponds to in-scope mental health/student support
            classes = list(self.domain_model.classes_)
            pos_idx = classes.index(1) if 1 in classes else np.argmax(classes)
            mh_confidence = float(domain_prob[pos_idx])
            is_mh = bool(mh_confidence >= 0.50)

            # 2. Intent Prediction
            intent_probs = self.intent_model.predict_proba([text])[0]
            intent_classes = list(self.intent_model.classes_)
            best_intent_idx = int(np.argmax(intent_probs))
            intent = str(intent_classes[best_intent_idx])
            intent_conf = float(intent_probs[best_intent_idx])

            # 3. Emotion Prediction
            emotion_probs = self.emotion_model.predict_proba([text])[0]
            emotion_classes = list(self.emotion_model.classes_)
            best_emo_idx = int(np.argmax(emotion_probs))
            emotion = str(emotion_classes[best_emo_idx])
            emotion_conf = float(emotion_probs[best_emo_idx])

            # 4. Tone Prediction
            if self.tone_model is not None:
                tone_probs = self.tone_model.predict_proba([text])[0]
                tone_classes = list(self.tone_model.classes_)
                best_tone_idx = int(np.argmax(tone_probs))
                tone = str(tone_classes[best_tone_idx])
            else:
                tone = "casual"

            # 5. Distress Scoring & Severity
            if self.distress_model is not None:
                raw_score = float(self.distress_model.predict([text])[0])
                distress_score = round(float(np.clip(raw_score, 0.0, 1.0)), 2)
            else:
                distress_score = 0.50

            # Incorporate conversation history into distress accumulation
            if history:
                history_weight = min(len(history) * 0.02, 0.10)
                distress_score = round(min(distress_score + history_weight, 1.0), 2)

            # Map distress score to suggested severity level
            if distress_score >= 0.85:
                severity = "severe" if distress_score < 0.95 else "high"
            elif distress_score >= 0.60:
                severity = "moderate"
            else:
                severity = "low"

            # 6. Response Strategy Decision & Prompt Modulation
            strategy, strategy_prompt = self._determine_strategy(intent, emotion, tone, distress_score)

            return {
                "is_mental_health": is_mh,
                "mental_health_confidence": round(mh_confidence, 2),
                "intent": intent,
                "intent_confidence": round(intent_conf, 2),
                "emotion": emotion,
                "emotion_confidence": round(emotion_conf, 2),
                "tone": tone,
                "distress_score": distress_score,
                "suggested_severity": severity,
                "recommended_strategy": strategy,
                "strategy_prompt": strategy_prompt,
                "ml_status": "active"
            }
        except Exception as e:
            logger.warning("Prediction error: %s", e)
            result["error"] = str(e)
            return result

    def _determine_strategy(self, intent: str, emotion: str, tone: str, distress: float):
        if distress >= 0.85 or intent == "crisis_expression":
            return (
                "CRISIS_DEESCALATION",
                "High distress detected. Respond with calm reassurance, compassionate grounding, and gently encourage seeking GC Guidance Office counselors or immediate help. Zero jokes, zero slang."
            )
        elif intent == "academic_stress":
            return (
                "ACADEMIC_PACING",
                "Academic stress detected (exams, capstone, grades, deadlines). Validate their hard work, normalize setbacks, and encourage breaking tasks into manageable pieces with small breaks."
            )
        elif emotion in ["anxious", "overwhelmed"] or intent == "seeking_coping_strategy":
            return (
                "GROUNDING_EXERCISE",
                "High anxiety or feeling overwhelmed. Offer a gentle, simple calming technique (like 4-7-8 breathing or 5-4-3-2-1 sensory grounding). Keep replies gentle and unhurried."
            )
        elif intent == "venting":
            return (
                "EMPATHETIC_LISTENING",
                "Student is venting emotional weight. Validate their feelings deeply first. Do NOT push quick fixes or unsolicited advice. Make them feel heard and safe."
            )
        elif intent == "relationship_issue":
            return (
                "SUPPORTIVE_EXPLORATION",
                "Interpersonal/relationship tension detected. Provide warm, non-judgmental validation and gently help them reflect on their feelings."
            )
        elif intent == "closure":
            return (
                "WARM_CLOSURE",
                "User is concluding the interaction. Reply warmly and concisely (1-2 sentences) wishing them well. Do NOT ask follow-up questions."
            )
        else:
            return (
                "SUPPORTIVE_PRESENCE",
                "Maintain a warm, genuine, college-student-appropriate empathetic presence."
            )


# Singleton instance for imports
_predictor = None


def get_predictor():
    global _predictor
    if _predictor is None:
        _predictor = MLPredictor()
    return _predictor


if __name__ == "__main__":
    predictor = get_predictor()

    input_data = None
    if len(sys.argv) > 1:
        raw_arg = sys.argv[1]
        try:
            input_data = json.loads(raw_arg)
        except json.JSONDecodeError:
            input_data = {"message": raw_arg}
    else:
        # Read from stdin if available
        try:
            stdin_content = sys.stdin.read().strip()
            if stdin_content:
                input_data = json.loads(stdin_content)
        except Exception:
            pass

    if not input_data:
        input_data = {"message": "Stressed ako sa capstone defense namin next week."}

    msg = input_data.get("message", "")
    hist = input_data.get("history", [])

    output = predictor.predict(msg, hist)
    print(json.dumps(output, ensure_ascii=False))
