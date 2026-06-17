import { BrowserRouter, Routes, Route, Link } from "react-router-dom";
import React, { lazy, Suspense } from "react";
import Dashboard from "./pages/Dashboard";
import "./App.css";

const Patients = lazy(() => import("./pages/Patients"));
const Doctors = lazy(() => import("./pages/Doctors"));

function App() {
  return (
    <BrowserRouter>
      <div className="container">
        <nav className="navbar">
          <Link to="/">Dashboard</Link>
          <Link to="/patients">Patients</Link>
          <Link to="/doctors">Doctors</Link>
        </nav>

        <Suspense fallback={<h2 className="loading">Loading...</h2>}>
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/patients" element={<Patients />} />
            <Route path="/doctors" element={<Doctors />} />
          </Routes>
        </Suspense>
      </div>
    </BrowserRouter>
  );
}

export default App;