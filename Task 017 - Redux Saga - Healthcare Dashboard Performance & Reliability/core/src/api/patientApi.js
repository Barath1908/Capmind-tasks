import axios from "axios";

const BASE_URL = "https://jsonplaceholder.typicode.com/users";

export const getPatients = () => {
  return axios.get(BASE_URL);
};

export const getPatientDetails = (id) => {
  return axios.get(`${BASE_URL}/${id}`);
};

export const registerPatient = (patientData) => {
  return axios.post(BASE_URL, patientData);
};