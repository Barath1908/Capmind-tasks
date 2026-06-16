import React from "react";

function StudentCard({
  name,
  age,
  skills,
  address,
  darkMode,
}) {
  const cardStyle = {
    border: "1px solid gray",
    padding: "15px",
    borderRadius: "10px",
    width: "350px",
    backgroundColor: darkMode ? "#333" : "#f5f5f5",
    color: darkMode ? "#fff" : "#000",
    transition: "0.3s ease",
  };

  return (
    <div style={cardStyle}>
      <h2>{name}</h2>

      <p>
        <strong>Age:</strong> {age}
      </p>

      <p>
        <strong>City:</strong> {address.city}
      </p>

      <p>
        <strong>Country:</strong> {address.country}
      </p>

      <h3>Skills</h3>

      <ul>
        {skills.map((skill, index) => (
          <li key={index}>{skill}</li>
        ))}
      </ul>
    </div>
  );
}

export default StudentCard;