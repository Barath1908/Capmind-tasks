import { useSelector, useDispatch } from "react-redux";
import { addPatient, deletePatient } from "../redux/patientSlice";

const usePatient = () => {

  const patients = useSelector(
    state => state.patient.patients
  );

  const dispatch = useDispatch();

  return {
    patients,

    addPatient: (patient) =>
      dispatch(addPatient(patient)),

    deletePatient: (id) =>
      dispatch(deletePatient(id))
  };
};

export default usePatient;