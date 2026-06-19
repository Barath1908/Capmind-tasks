import usePatient from "../hooks/usePatient";

function PatientList() {
  const { patients, deletePatient } = usePatient();

  return (
    <>
      <h2>Patient List</h2>

      {patients.length === 0 ? (<p className="empty">No Patients Found</p>) : (
        <ul>
          {patients.map((patient) => (
            <li key={patient.id}>
              <span>{patient.name}</span>
                <button onClick={() => deletePatient(patient.id)}>Delete</button>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}

export default PatientList;