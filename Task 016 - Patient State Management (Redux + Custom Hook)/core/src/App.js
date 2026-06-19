import "./App.css";
import AddPatient from "./components/AddPatient";
import PatientList from "./components/PatientList";

function App() {
  return (
    <div className="container">
      <h1>Patient Management</h1>

      <AddPatient />

      <div className="patient-list">
        <PatientList />
      </div>
    </div>
  );
}

export default App;