import React, { useState } from "react";
import { useDispatch } from "react-redux";

import { submitPatientForm } from "../redux/actions";

function PatientForm() {
  const dispatch = useDispatch();

  const [formData, setFormData] = useState({name: "",age: "",disease: "",doctor: ""});

  const handleChange = (e) => {
    setFormData({...formData,[e.target.name]: e.target.value});
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    dispatch(submitPatientForm(formData));

    alert("Patient submitted");

    setFormData({name: "",age: "",disease: "",doctor: ""});
  };

  return (
    <div className="section">
      <h2>Patient Registration</h2>

      <form onSubmit={handleSubmit}>
        <input type="text" name="name" placeholder="Patient Name" value={formData.name} onChange={handleChange} required/>
        <input type="number" name="age" placeholder="Age" value={formData.age} onChange={handleChange} required/>
        <input type="text" name="disease" placeholder="Disease" value={formData.disease} onChange={handleChange} required/>
        <input type="text" name="doctor" placeholder="Doctor Assigned" value={formData.doctor} onChange={handleChange} required/>
        <button type="submit">Submit</button>
      </form>
    </div>
  );
}

export default PatientForm;