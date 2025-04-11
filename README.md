🗳️ VotePoll - Online Polling and Voting System
VotePoll is a dynamic, web-based polling and voting platform designed to help users create, share, and manage polls effortlessly. Whether you're seeking feedback, making group decisions, or collecting opinions, VotePoll offers a streamlined and user-friendly experience.

Built with PHP, MySQL, and Tailwind CSS, the platform ensures smooth performance, responsive design, and real-time data interaction.

📋 Features
Create Custom Polls: Design polls with multiple options and customizable settings

Real-time Results: Watch results update instantly as votes come in

Anonymous Voting: Get unbiased feedback through anonymous submissions

Advanced Analytics: View detailed insights with charts and data visualizations

User Management: Secure user registration and authentication

Mobile Responsive: Fully responsive layout for all devices

🛠️ Technologies Used
Frontend: HTML5, CSS3, Tailwind CSS, JavaScript

Backend: PHP

Database: MySQL

Libraries: Font Awesome, Chart.js

📦 Installation
🔧 Prerequisites
XAMPP (or any PHP/MySQL development environment)

Web browser

🚀 Setup Instructions
Clone the repository

bash
Copy
Edit
git clone https://github.com/yourusername/votepoll.git
Set up the database

Start XAMPP and ensure Apache and MySQL services are running

Open phpMyAdmin

Create a new database named poll_system

Import the schema: database/poll_system.sql

Configure the application

Move the project folder to your XAMPP htdocs directory

Update database connection in index.php if needed:

php
Copy
Edit
$conn = mysqli_connect("localhost", "root", "", "poll_system");
Access the app

Navigate to: http://localhost/votepoll/index.php

🔑 Usage
📌 Creating a Poll
Sign up or log in

Click "Create Poll" in the navigation menu

Enter your question, add options, and configure the settings

Click "Create" to publish

✅ Voting on a Poll
Browse available polls on the homepage or under "Browse Polls"

Choose a poll and select your option

Submit your vote and view real-time results

📊 Viewing Results
Results appear after voting

Revisit results via "Browse Polls"

Detailed stats with vote counts and percentages included

👥 Use Cases
Business & Marketing: Feature prioritization, customer surveys

Education: Student feedback, classroom polls

Community & Events: Group planning, decision-making

Politics & Governance: Public opinion, campaign insights

📃 License
This project is licensed under the MIT License.

🙏 Acknowledgments
Tailwind CSS — for responsive design framework

Font Awesome — for icons

Unsplash — for stock images

All contributors and testers who provided valuable feedback

