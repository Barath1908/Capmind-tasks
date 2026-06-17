import React from "react";
import { doctors } from "../data";

function Doctors() {
  console.log("Doctors Component Rendered");


  return (
    <div className="page">
      <h2>Doctors</h2>

      <div className="doctor-list">
        {doctors.map((doctor, index) => (
          <div key={index} className="doctor-card">
            {doctor}
          </div>
        ))}
      </div>
    </div>
  );
}

export default Doctors;