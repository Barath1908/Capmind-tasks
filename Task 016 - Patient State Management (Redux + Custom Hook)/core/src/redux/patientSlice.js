import { createSlice } from "@reduxjs/toolkit";

const patientSlice = createSlice({
  name: "patients",

  initialState: {
    patients: []
  },

  reducers: {
    addPatient: (state, action) => {
      state.patients.push(action.payload);
    },

    deletePatient: (state, action) => {
      state.patients = state.patients.filter(
        patient => patient.id !== action.payload
      );
    }
  }
});

export const { addPatient, deletePatient } = patientSlice.actions;

export default patientSlice.reducer;