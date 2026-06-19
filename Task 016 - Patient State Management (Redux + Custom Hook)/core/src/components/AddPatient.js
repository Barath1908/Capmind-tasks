import { useState } from "react";
import usePatient from "../hooks/usePatient";

function AddPatient() {
  const [name, setName] = useState("");
  const { addPatient } = usePatient();

  const handleAdd = () => {
    if (!name.trim()) return;

    addPatient({
      id: Date.now(),
      name,
    });

    setName("");
  };

  return (
    <>
      <h2>Add Patient</h2>

      <div className="input-group">
        <input type="text" placeholder="Enter patient name" value={name} onChange={(e) => setName(e.target.value)}/>
        <button onClick={handleAdd}>Add</button>
      </div>
    </>
  );
}

export default AddPatient;