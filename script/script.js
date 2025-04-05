// Script for handling user list and chat area toggle
document.addEventListener("DOMContentLoaded", function () {
  const userList = document.querySelector(".user-list");
  const chatArea = document.querySelector(".chat-area");
  const backButton = document.getElementById("backToUsers");
  const userItems = document.querySelectorAll(".user-item");

  userItems.forEach((item) => {
    item.addEventListener("click", () => {
      document.querySelector(".chat-container").classList.add("show-chat");
      document.querySelector(".chat-container").classList.remove("show-users");

      // Set user details in chat header
      const name = item.querySelector(".user-name").textContent;
      const pic = item.querySelector(".profile-pic").cloneNode(true);

      document.querySelector(".chat-header .chat-username").textContent = name;
      document.querySelector(".chat-header .chat-profile-pic").replaceWith(pic);
    });
  });

  backButton.addEventListener("click", () => {
    document.querySelector(".chat-container").classList.remove("show-chat");
    document.querySelector(".chat-container").classList.add("show-users");
  });
});
// Script for search functionality
document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchUsers");
  const userItems = document.querySelectorAll(".user-item");

  searchInput.addEventListener("keyup", function () {
    const query = this.value.toLowerCase();

    userItems.forEach((item) => {
      const name = item.querySelector(".user-name").textContent.toLowerCase();
      item.style.display = name.includes(query) ? "flex" : "none";
    });
  });
});
document.addEventListener("DOMContentLoaded", function () {
  const chatContent = document.querySelector(".chat-content");
  const messageInput = document.getElementById("messageInput");
  const sendMessageButton = document.getElementById("sendMessage");
  const chatHeader = document.querySelector(".chat-header");
  let currentReceiverId = null;

  function formatDate(dateString) {
    if (!dateString) return ""; // Handle empty or invalid date strings
    const date = new Date(dateString); // Parse the date string
    if (isNaN(date.getTime())) return ""; // Handle invalid date objects

    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    if (date.toDateString() === today.toDateString()) {
      return "Today";
    } else if (date.toDateString() === yesterday.toDateString()) {
      return "Yesterday";
    } else {
      const options = {
        day: "2-digit",
        month: "short",
        year: "2-digit",
      };
      return date.toLocaleDateString("en-GB", options).replace(/ /g, "-");
    }
  }

  function fetchMessages(receiverId) {
    const previousMessageCount = chatContent.childElementCount; // Count current messages

    fetch(`logic/fetch_messages.php?receiver_id=${receiverId}`)
      .then((response) => response.json())
      .then((data) => {
        const messages = data.messages;

        // Sort messages by sent_on (date) and sent_at (time)
        messages.sort((a, b) => {
          return (
            new Date(a.sent_on + " " + a.sent_at) -
            new Date(b.sent_on + " " + b.sent_at)
          );
        });

        let lastDate = null;
        const newContent = document.createDocumentFragment();

        messages.forEach((msg) => {
          const messageDate = msg.sent_on;
          if (messageDate !== lastDate) {
            // Format the date
            const formattedDate = formatDate(messageDate);
            if (formattedDate) {
              const dateHeader = document.createElement("div");
              dateHeader.classList.add("date-header");
              dateHeader.textContent = formattedDate;
              dateHeader.style.textAlign = "center"; // Align in center
              newContent.appendChild(dateHeader);
            }
            lastDate = messageDate;
          }

          // Add message
          const messageElement = document.createElement("div");
          messageElement.classList.add(
            "message",
            msg.is_sender ? "sent" : "received"
          );
          messageElement.innerHTML = `
                    <p>${msg.message}</p>
                    <span class="timestamp">${msg.sent_at}</span>
                `;
          newContent.appendChild(messageElement);
        });

        // Replace chat content with new messages
        chatContent.innerHTML = "";
        chatContent.appendChild(newContent);

        // Scroll to the bottom if the number of messages increases
        const currentMessageCount = chatContent.childElementCount;
        if (currentMessageCount > previousMessageCount) {
          chatContent.scrollTop = chatContent.scrollHeight;
        }
      })
      .catch((error) => console.error("Error fetching messages:", error));
  }

  // Function to format the date (Today, Yesterday, or exact date)
  function formatDate(dateString) {
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    const messageDate = new Date(dateString);

    if (messageDate.toDateString() === today.toDateString()) {
      return "Today";
    } else if (messageDate.toDateString() === yesterday.toDateString()) {
      return "Yesterday";
    } else {
      return messageDate.toLocaleDateString(); // Show full date if not today/yesterday
    }
  }

  function sendMessage() {
    const message = messageInput.value.trim();
    if (message !== "" && currentReceiverId) {
      fetch("logic/send_message.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          receiver_id: currentReceiverId,
          message,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            messageInput.value = "";
            fetchMessages(currentReceiverId);
          } else {
            console.error(data.error || "Failed to send message.");
          }
        });
    }
  }

  function openChat(userItem) {
    currentReceiverId = userItem.dataset.user;
    localStorage.setItem("lastOpenedChat", currentReceiverId); // Save to localStorage
    const name = userItem.querySelector(".user-name").textContent;
    const pic = userItem.querySelector(".profile-pic").cloneNode(true);

    chatHeader.innerHTML = "";
    chatHeader.appendChild(pic);
    const nameSpan = document.createElement("span");
    nameSpan.classList.add("chat-username");
    nameSpan.textContent = name;
    chatHeader.appendChild(nameSpan);

    fetchMessages(currentReceiverId);
  }

  sendMessageButton.addEventListener("click", sendMessage);
  messageInput.addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      sendMessage();
    }
  });

  document.querySelectorAll(".user-item").forEach((item) => {
    item.addEventListener("click", () => {
      openChat(item);
    });
  });

  // Open the last opened chat on page load
  const lastOpenedChat = localStorage.getItem("lastOpenedChat");
  if (lastOpenedChat) {
    const lastChatItem = document.querySelector(
      `.user-item[data-user='${lastOpenedChat}']`
    );
    if (lastChatItem) {
      openChat(lastChatItem);
    }
  } else {
    const firstUser = document.querySelector(".user-item");
    if (firstUser) {
      openChat(firstUser);
    }
  }

  // Real-time message fetching
  setInterval(() => {
    if (currentReceiverId) {
      fetchMessages(currentReceiverId);
    }
  }, 3000); // Fetch messages every 3 seconds
});
// Theme toggle functionality
document.addEventListener("DOMContentLoaded", function () {
  const themeToggleCheckbox = document.getElementById("themeToggleCheckbox");
  const body = document.body;

  // Load saved theme from localStorage
  const savedTheme = localStorage.getItem("theme") || "light-theme";
  body.classList.add(savedTheme);
  themeToggleCheckbox.checked = savedTheme === "dark-theme";

  themeToggleCheckbox.addEventListener("change", function () {
    const newTheme = themeToggleCheckbox.checked ? "dark-theme" : "light-theme";
    body.classList.remove("dark-theme", "light-theme");
    body.classList.add(newTheme);
    localStorage.setItem("theme", newTheme);
  });

  document
    .getElementById("visitProfile")
    .addEventListener("click", function () {
      window.location.href = "profile.php"; // Adjust the URL as needed
    });
});
document.addEventListener("DOMContentLoaded", function () {
  const extraOptionsBtn = document.getElementById("extraOptions");
  const extraOptionsPopup = document.getElementById("extraOptionsPopup");
  const sendLocationBtn = document.getElementById("sendLocation");

  // Toggle popup visibility
  extraOptionsBtn.addEventListener("click", function () {
    extraOptionsPopup.style.display =
      extraOptionsPopup.style.display === "block" ? "none" : "block";
  });

  // Close popup when clicking outside
  document.addEventListener("click", function (event) {
    if (
      !extraOptionsBtn.contains(event.target) &&
      !extraOptionsPopup.contains(event.target)
    ) {
      extraOptionsPopup.style.display = "none";
    }
  });

  // Send geolocation with address
  sendLocationBtn.addEventListener("click", function () {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const latitude = position.coords.latitude;
          const longitude = position.coords.longitude;

          // Use a reverse geocoding API to fetch the address
          const geocodingApiUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`;

          fetch(geocodingApiUrl)
            .then((response) => response.json())
            .then((data) => {
              const address = data.display_name || "Address not available";
              const locationMessage = `My location: ${address} (https://www.google.com/maps?q=${latitude},${longitude})`;
              document.getElementById("messageInput").value = locationMessage;
            })
            .catch((error) => {
              console.error("Error fetching address:", error);
              alert("Unable to fetch address. Please try again.");
            });
        },
        function (error) {
          alert("Unable to fetch location. Please try again.");
        }
      );
    } else {
      alert("Geolocation is not supported by your browser.");
    }
  });
});
