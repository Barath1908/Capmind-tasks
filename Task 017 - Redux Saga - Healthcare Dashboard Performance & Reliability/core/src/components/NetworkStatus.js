import React, { useEffect, useState } from "react";

function NetworkStatus() {
  const [online, setOnline] = useState(navigator.onLine);

  useEffect(() => {
    const handleOnline = () => {
      setOnline(true);
    };

    const handleOffline = () => {
      setOnline(false);
    };

    window.addEventListener(
      "online",
      handleOnline
    );

    window.addEventListener(
      "offline",
      handleOffline
    );

    return () => {
      window.removeEventListener(
        "online",
        handleOnline
      );

      window.removeEventListener(
        "offline",
        handleOffline
      );
    };
  }, []);

  return (
    <div className="network-status">
      <h3>
        Status :
        {" "}
        {online ? "🟢 Online" : "🔴 Offline"}
      </h3>
    </div>
  );
}

export default NetworkStatus;