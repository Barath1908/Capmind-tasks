import React from "react";

function PatientCard({ patient }) {
  console.log("Patient Card Rendered:", patient.name);

  return (
    <div className="patient-card">
      <h4>{patient.name}</h4>
      <p>Age: {patient.age}</p>
    </div>
  );
}

export default React.memo(PatientCard);