import React, { useState } from "react";
import StudentCard from "./Components/StudentCard";

function App() {
  const [likes, setLikes] = useState(0);
  const [darkMode, setDarkMode] = useState(false);

  const studentName = "Barath";
  const studentAge = 23;

  const skills = ["React", "JavaScript", "HTML", "CSS"];

  const address = {
    city: "Trichy",
    country: "India",
  };

  const appStyle = {
    backgroundColor: darkMode ? "#1e1e1e" : "#ffffff",
    color: darkMode ? "#ffffff" : "#000000",
    minHeight: "100vh",
    padding: "20px",
    transition: "0.3s ease",
  };

  return (
    <div style={appStyle}>
      <h1>Student Profile Dashboard</h1>

      <button
        onClick={() => setDarkMode(!darkMode)}
        style={{
          padding: "10px",
          marginBottom: "20px",
          cursor: "pointer",
        }}
      >
        {darkMode ? "Light Mode" : "Dark Mode"}
      </button>

      <StudentCard
        name={studentName}
        age={studentAge}
        skills={skills}
        address={address}
        darkMode={darkMode}
      />

      <hr />

      <h2>Likes Counter</h2>

      <p>Total Likes: {likes}</p>

      <button onClick={() => setLikes(likes + 1)}>
        Like
      </button>
    </div>
  );
}

export default App;