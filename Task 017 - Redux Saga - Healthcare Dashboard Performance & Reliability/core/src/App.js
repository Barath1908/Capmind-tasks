import React, { useEffect } from "react";
import { useDispatch } from "react-redux";

import { processQueue } from "./redux/actions";

import NetworkStatus from "./components/NetworkStatus";
import PatientForm from "./components/PatientForm";
import PatientList from "./components/PatientList";
import PatientDetails from "./components/PatientDetails";

import "./App.css";

function App() {
  const dispatch = useDispatch();

  useEffect(() => {
    const handleOnline = () => {
      dispatch(processQueue());
    };

    window.addEventListener("online", handleOnline);

    return () => {
      window.removeEventListener("online", handleOnline);
    };
  }, []);

  return (
    <div className="App">
      <h1>Healthcare Dashboard</h1>

      <NetworkStatus />

      <PatientForm />

      <PatientList />

      <PatientDetails />
    </div>
  );
}

export default App;