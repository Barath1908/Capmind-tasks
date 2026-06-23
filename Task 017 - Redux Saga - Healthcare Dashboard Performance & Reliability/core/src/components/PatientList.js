import React, { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";

import {fetchPatients, nextPage, previousPage, fetchPatientDetails} from "../redux/actions";

function PatientList() {
  const dispatch = useDispatch();

  const patients = useSelector((state) => state.patients);
  const currentPage = useSelector((state) => state.currentPage);
  const pageSize = useSelector((state) => state.pageSize);
  const loading = useSelector((state) => state.loading);
  const error = useSelector((state) => state.error);

  useEffect(() => {
    dispatch(fetchPatients());
  }, []);

  const startIndex = (currentPage - 1) * pageSize;

  const visiblePatients = patients.slice(
    startIndex,
    startIndex + pageSize
  );

  return (
    <div className="section">
      <h2>Patient List</h2>

      {loading && <h3>Loading...</h3>}

      {error && <h3>{error}</h3>}

      {visiblePatients.map((patient) => (
            <div className="patient-card" key={patient.id}>
                <h3>{patient.name}</h3>
                <button onClick={() => dispatch(fetchPatientDetails(patient.id))}>View Details</button>
            </div>
        ))}

      <div className="pagination">
        <button onClick={() => dispatch(previousPage())} disabled={currentPage === 1}>Previous</button>
        <button onClick={() => dispatch(nextPage())} disabled={currentPage >= 2}>Next</button>
      </div>
    </div>
  );
}

export default PatientList;