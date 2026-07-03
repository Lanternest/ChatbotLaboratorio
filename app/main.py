from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routers import chat, email

app = FastAPI(
    title="Chatbot Laboratorio - Hospital Universitario UNCuyo",
    description="API para el chatbot de instructivos de análisis clínicos",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, reemplazar con el dominio del hospital
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(chat.router, prefix="/api")
app.include_router(email.router, prefix="/api")

@app.get("/")
def health_check():
    return {"status": "ok", "servicio": "Chatbot Laboratorio HU UNCuyo"}