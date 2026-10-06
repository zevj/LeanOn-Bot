"""
LeanOn-Bot ML FastAPI Microservice
Provides REST endpoints for real-time inference, model retraining, and health monitoring.
"""

import os
import json
from typing import List, Optional, Dict, Any
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

from predict import get_predictor
from train import train_models

app = FastAPI(
    title="LeanOn-Bot ML Service",
    description="Machine Learning service for student mental health NLP classification, context understanding, and response strategy",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


class PredictRequest(BaseModel):
    message: str
    history: Optional[List[Dict[str, Any]]] = []


class PredictResponse(BaseModel):
    is_mental_health: bool
    mental_health_confidence: float
    intent: str
    intent_confidence: float
    emotion: str
    emotion_confidence: float
    tone: str
    distress_score: float
    suggested_severity: str
    recommended_strategy: str
    strategy_prompt: str
    ml_status: str
    error: Optional[str] = None


@app.get("/health")
def health_check():
    predictor = get_predictor()
    meta_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "metadata.json")
    meta = {}
    if os.path.exists(meta_path):
        try:
            with open(meta_path, "r", encoding="utf-8") as f:
                meta = json.load(f)
        except Exception:
            pass

    return {
        "status": "healthy" if predictor.is_ready else "initializing",
        "models_loaded": predictor.is_ready,
        "version": meta.get("version", "1.0.0"),
        "trained_at": meta.get("trained_at"),
        "total_samples": meta.get("total_samples", 0)
    }


@app.get("/metrics")
def get_metrics():
    meta_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "models", "metadata.json")
    if not os.path.exists(meta_path):
        raise HTTPException(status_code=404, detail="No model metadata found. Please train models first.")

    with open(meta_path, "r", encoding="utf-8") as f:
        meta = json.load(f)

    return meta


@app.post("/predict", response_model=PredictResponse)
def predict(payload: PredictRequest):
    predictor = get_predictor()
    result = predictor.predict(payload.message, payload.history)
    return result


@app.post("/train")
def train():
    try:
        metadata = train_models()
        predictor = get_predictor()
        predictor.load_models()
        return {
            "success": True,
            "message": "Models successfully retrained and reloaded.",
            "metadata": metadata
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Training failed: {str(e)}")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8001)
