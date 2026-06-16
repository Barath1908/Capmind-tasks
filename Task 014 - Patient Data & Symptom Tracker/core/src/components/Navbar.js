// src/components/Navbar.js

import React, { useContext } from "react";
import { PatientContext } from "../context/PatientContext";

const Navbar = () => {
  const patient = useContext(PatientContext);

  return (
    <nav
      style={{
        background: "#333",
        color: "white",
        padding: "10px",
      }}
    >
      Welcome, {patient.name}
    </nav>
  );
};

export default Navbar;