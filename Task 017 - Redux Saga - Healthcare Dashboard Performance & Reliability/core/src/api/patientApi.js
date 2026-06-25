import axios from "axios";

const BASE_URL = "https://jsonplaceholder.typicode.com/users";

export const getPatients = async () => {
  return await axios.get(BASE_URL);
};

export const getPatientDetails = async (id) => {
  return await axios.get(`${BASE_URL}/${id}`);
};

export const registerPatient = async (patientData) => {
  return await axios.post(BASE_URL, patientData);
};

