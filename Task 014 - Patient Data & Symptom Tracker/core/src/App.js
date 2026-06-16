import React, {useContext,useEffect,useRef,useState,} from "react";

import axios from "axios";

import Navbar from "./components/Navbar";
import { PatientContext } from "./context/PatientContext";

function App() {
  console.log("Component Rendered");

  // Context
  const patient = useContext(PatientContext);

  // API Data
  const [patients, setPatients] = useState([]);

  // Symptoms List
  const [symptoms, setSymptoms] = useState([]);

  // State Input Experiment
  const [stateInput, setStateInput] = useState("");

  // Ref Inputs
  const symptomRef = useRef(null);
  const refInput = useRef(null);

  // API Call
  useEffect(() => {
    axios.get("https://jsonplaceholder.typicode.com/users").then((response) => {
        setPatients(response.data);
      }).catch((error) => {
        console.log(error);
      });}, []);

  // Auto Focus
  useEffect(() => {
    symptomRef.current.focus();
  }, []);

  // Add Symptom
  const addSymptom = () => {
    const symptom = symptomRef.current.value;

    if (!symptom.trim()) return;

    setSymptoms([...symptoms, symptom]);

    symptomRef.current.value = "";
    symptomRef.current.focus();
  };

  return (
    <div style={{ padding: "20px" }}>
      <Navbar />
      {/* Patient Info */}
      <section>
        <h2>Patient Info (Context)</h2>
        <p><strong>Name:</strong> {patient.name}</p>
        <p><strong>Email:</strong> {patient.email}</p>
      </section>
      <hr />
      {/* API Data */}
      <section>
        <h2>Patient List (useEffect + Axios)</h2>
        <ul>
          {patients.map((user) => (
            <li key={user.id}>{user.name}</li>
          ))}
        </ul>
      </section>
      <hr />
      {/* Symptom Input */}
      <section>
        <h2>Symptom Tracker (useRef)</h2>
        <input ref={symptomRef} placeholder="Enter symptom"/>
        <button onClick={addSymptom}>Add Symptom</button>
      </section>
      <hr />
      {/* Symptoms */}
      <section>
        <h2>Symptoms List</h2>
        <ul>{symptoms.map((item, index) => (
            <li key={index}>{item}</li>
          ))}
        </ul>
      </section>
      <hr />
      {/* useState vs useRef */}
      <section>
        <h2>useState vs useRef Experiment</h2>
        <div>
          <h4>Input using useState</h4>
          <input value={stateInput} onChange={(e) => setStateInput(e.target.value)} placeholder="State Input"/>
        </div>
        <br />
        <div>
          <h4>Input using useRef</h4>
          <input ref={refInput} placeholder="Ref Input"/>
        </div>
      </section>
    </div>
  );
}

export default App;