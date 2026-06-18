import React, { useState, useMemo, useCallback } from "react";
import PatientCard from "../components/PatientCard";
import { patients } from "../data";

function Patients() {
  console.log("Patients Component Rendered");

  const [search, setSearch] = useState("");


  const handleSearch = ((e) => {
    setSearch(e.target.value);
  });

  const handleClick = useCallback(() => {
    console.log("Button Clicked");
  }, []);

  const filteredPatients = useMemo(() => {
    console.log("Filtering Patients");

    return patients.filter((patient) =>
      patient.name.toLowerCase().includes(search.toLowerCase())
    );
  }, [search]);

  return (
    <div className="page">
      <h2>Patients</h2>

      <input
        className="search-input"
        type="text"
        placeholder="Search Patient..."
        value={search}
        onChange={handleSearch}
      />

      <button className="btn" onClick={handleClick}>
        Test useCallback
      </button>

      <div className="cards-container">
        {filteredPatients.map((patient) => (
          <PatientCard
            key={patient.id}
            patient={patient}
          />
        ))}
      </div>
    </div>
  );
}

export default Patients;
