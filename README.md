# VotePoll - Online Polling and Voting System
VotePoll is a dynamic, web-based polling and voting platform designed to help users create, share, and manage polls effortlessly. Whether you're seeking feedback, making group decisions, or simply collecting opinions, otePoll offers a streamlined and user-friendly experience. Built with PHP, MySQL, and TailwindCSS, the platform ensures smooth performance, responsive design, and real-time data interaction.

## 📋 Features
- **Create Custom Polls**: Design polls with multiple options and customize settings
- **Real-time Results**: Watch results update instantly as votes come in
- **Anonymous Voting**: Enable anonymous voting to get unbiased feedback
- **Advanced Analytics**: View detailed insights with beautiful charts and data visualization
- **User Management**: Secure account creation and authentication
- **Mobile Responsive**: Works seamlessly on all devices

## 🛠️ Technologies Used
- **Frontend**: HTML5, CSS3, Tailwind CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Libraries**: Font Awesome, Chart.js

## 📦 Installation
### Prerequisites
- XAMPP (or any PHP development environment with MySQL)
- Web Browser

### Setup Instructions
1. **Clone the repository**
```
git clone https://github.com/yourusername/votepoll.git
```

2. **Set up the database**
- Start XAMPP and ensure Apache and MySQL services are running
- Open phpMyAdmin (http://localhost/phpmyadmin)
- Create a new database named poll_system
- Import the database schema from database/poll_system.sql

3. **Configure the application**
- Move the project folder to your XAMPP htdocs directory
- Update database connection settings in index.php if necessary:
```php
$conn = mysqli_connect("localhost", "root", "", "poll_system");
```

4. **Access the application**
- Open your browser and navigate to: http://localhost/Vote/Vote/index.php

## 🔑 Usage
### Creating a Poll
1. Sign up for an account or log in
2. Click on "Create Poll" in the navigation
3. Fill in your question, add options, and configure settings
4. Click "Create" to publish your poll

### Voting on a Poll
1. Browse available polls from the homepage or "Browse Polls" section
2. Select a poll to vote on
3. Choose your preferred option
4. Submit your vote to see the results

### Viewing Results
1. After voting, you'll be shown the current results
2. Access any poll's results page through the "Browse Polls" section
3. View detailed analytics including vote counts and percentages

## 👥 Use Cases
- **Business & Marketing**: Product feature prioritization, customer satisfaction surveys
- **Education**: Student feedback, classroom engagement, curriculum planning
- **Community & Events**: Event planning, community decisions, group activities
- **Politics & Governance**: Public opinion surveys, policy feedback, campaign engagement

## 📃 License
This project is licensed under the MIT License.

## 🙏 Acknowledgments
- Tailwind CSS for the responsive design framework
- Font Awesome for the icons
- Unsplash for stock images
- All contributors and testers who provided feedback 
