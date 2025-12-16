# ⚓ Battle Ships Crews

FullStack Project – Battleship game in PHP & MySQL

---

## 📌 Project Overview

**Battle Ships Crews** is a two-player web-based battleship game. Each player places their ships on a grid and then takes turns firing shots to sink the opponent’s fleet. The game relies on a **FullStack architecture**, with server-side game logic and an interactive web interface.

The project was initially designed to be developed in pairs, but it was ultimately completed entirely independently.

---

## 🎯 Learning Objectives

* Design a dynamic web application
* Implement server-side game logic
* Use a relational database (MySQL)
* Manage user sessions
* Organize a project in a clean and maintainable way
* Work with Git and GitHub

---

## 🛠️ Technologies Used

* **Front-end**:

  * HTML5
  * CSS3 (animations and responsive interface)
  * JavaScript (user interactions)

* **Back-end**:

  * PHP (game logic, sessions, queries)
  * MySQL (storage of grids, shots, and game states)

* **Tools**:

  * Git & GitHub
  * VS Code
  * WSL / Linux

---

## 🗂️ Project Structure

```text
Battle-Ships-Crews/
├── assets/
│   ├── css/
│   ├── js/
│   └── sounds/
├── game/
│   ├── placement.php
│   ├── tir.php
│   ├── etat.php
│   └── reset.php
├── scripts/
│   └── sql-connect.php
├── index.php
├── database.sql
└── README.md
```

---

## 🚢 Game Rules

* Each player has a grid (e.g. 10×10)
* Available ships:

  * Aircraft carrier
  * Cruiser
  * Destroyers
  * Submarine
* Players place their ships before the game starts
* Turns are played alternately
* Hits and misses are visually marked
* The game ends when all ships of one player are sunk

---

## ▶️ Run the Project Locally

1. Clone the GitHub repository

   ```bash
   git clone <repository-url>
   ```

2. Place the project in the web server directory

   ```bash
   /var/www/
   ```

3. Import the database

   * Open phpMyAdmin or MySQL
   * Import the `database.sql` file

4. Configure the database connection

   * Edit credentials in `scripts/sql-connect.php`

5. Access the project via your browser

   ```text
   http://localhost/Battle-Ships-Crews
   ```

---

## ✨ Main Features

* Manual ship placement
* Turn-based gameplay management
* Game state persistence in the database
* Dynamic display of hits and misses
* Game reset functionality
* Visual interface with animations

---

## 📈 Possible Improvements

* Real-time synchronization (AJAX / WebSockets)
* Spectator mode
* In-game chat
* AI opponent for solo play
* Online hosting

---

## 👤 Author

Project developed by Alexis Mathieu

Computer Science student – Bachelor Year 1

---

## 📄 License

Educational project.
