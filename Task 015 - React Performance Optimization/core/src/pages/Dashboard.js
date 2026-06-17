import React, { useMemo } from "react";
import { patients, doctors } from "../data";

function Dashboard() {
  const totalPatients = useMemo(() => patients.length, []);
  const totalDoctors = useMemo(() => doctors.length, []);

  return (
    <div className="page">
      <h2>Healthcare Dashboard</h2>

      <div className="stats-container">
        <div className="stat-card">
          <h3>{totalPatients}</h3>
          <p>Total Patients</p>
        </div>

        <div className="stat-card">
          <h3>{totalDoctors}</h3>
          <p>Total Doctors</p>
        </div>
      </div>
    </div>
  );
}

export default Dashboard;