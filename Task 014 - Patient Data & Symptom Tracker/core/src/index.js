import React from "react";
import ReactDOM from "react-dom/client";

import App from "./App";

import {
  PatientProvider,
} from "./context/PatientContext";

const root = ReactDOM.createRoot(
  document.getElementById("root")
);

root.render(
  <PatientProvider>
    <App />
  </PatientProvider>
);