from fastapi import FastAPI
from pydantic import BaseModel


app = FastAPI(
    title="SmartCare AI Engine",
    description="AI Health Monitoring System",
    version="1.0"
)


class PatientHealth(BaseModel):
    patient_name: str
    blood_pressure: int
    blood_sugar: float
    heart_rate: int



@app.get("/")
def home():

    return {
        "message": "SmartCare AI Engine is running",
        "status": "active"
    }



@app.post("/analyse")
def analyse_health(data: PatientHealth):

    risk = "LOW"
    alert = "Normal health reading"

    
    # Blood pressure checking
    if data.blood_pressure >= 160:
        risk = "HIGH"
        alert = "High blood pressure detected"


    # Blood sugar checking
    if data.blood_sugar < 4:
        risk = "HIGH"
        alert = "Possible hypoglycemia detected"


    elif data.blood_sugar > 15:
        risk = "HIGH"
        alert = "High blood glucose detected"


    # Heart rate checking
    if data.heart_rate > 120:
        risk = "HIGH"
        alert = "Abnormal heart rate detected"



    return {

        "patient": data.patient_name,

        "risk_level": risk,

        "alert": alert,

        "recommendation":
        "Nurse attention required"
        if risk == "HIGH"
        else "Continue monitoring"

    }