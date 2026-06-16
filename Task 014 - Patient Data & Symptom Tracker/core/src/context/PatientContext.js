// src/context/PatientContext.js

import { createContext } from "react";

export const PatientContext = createContext();

export const PatientProvider = ({ children }) => {
  const patient = {
    name: "John Doe",
    email: "patient@test.com",
  };

  return (
    <PatientContext.Provider value={patient}>
      {children}
    </PatientContext.Provider>
  );
};