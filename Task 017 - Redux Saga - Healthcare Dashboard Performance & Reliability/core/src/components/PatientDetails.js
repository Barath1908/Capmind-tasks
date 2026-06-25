import React from "react";
import { useSelector } from "react-redux";

function PatientDetails() {

  const patient = useSelector(
    (state) => state.patientDetails
  );

  return (
    <div className="section">
      <h2>Patient Details</h2>

      {!patient ? (
        <p>Select a patient from the list.</p>
      ) : (
        <div className="details-box">
          <h3>{patient.name}</h3>
          <p><strong>Email:</strong> {patient.email}</p>
          <p><strong>Phone:</strong> {patient.phone}</p>
          <p><strong>Website:</strong> {patient.website}</p>
          <p><strong>City:</strong> {patient.address.city}</p>
        </div>
      )}
    </div>
  );
}

export default PatientDetails;