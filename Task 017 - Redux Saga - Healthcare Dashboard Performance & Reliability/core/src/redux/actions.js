import * as types from "./types";

// ---------------- Patients ----------------

export const fetchPatients = () => ({
  type: types.FETCH_PATIENTS,
});

export const setPatients = (patients) => ({
  type: types.SET_PATIENTS,
  payload: patients,
});

// ---------------- Pagination ----------------

export const nextPage = () => ({
  type: types.NEXT_PAGE,
});

export const previousPage = () => ({
  type: types.PREVIOUS_PAGE,
});

// ---------------- Form ----------------

export const submitPatientForm = (patient) => ({
  type: types.SUBMIT_PATIENT_FORM,
  payload: patient,
});

export const queuePatientForm = (patient) => ({
  type: types.QUEUE_PATIENT_FORM,
  payload: patient,
});

export const processQueue = () => ({
  type: types.PROCESS_QUEUE,
});

export const removeQueuedForm = (index) => ({
  type: types.REMOVE_QUEUED_FORM,
  payload: index,
});

// ---------------- Patient Details ----------------

export const fetchPatientDetails = (id) => ({
  type: types.FETCH_PATIENT_DETAILS,
  payload: id,
});

export const setPatientDetails = (patient) => ({
  type: types.SET_PATIENT_DETAILS,
  payload: patient,
});

// ---------------- Loading ----------------

export const setLoading = (status) => ({
  type: types.SET_LOADING,
  payload: status,
});

// ---------------- Error ----------------

export const setError = (error) => ({
  type: types.SET_ERROR,
  payload: error,
});