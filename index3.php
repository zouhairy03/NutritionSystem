<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            scroll-behavior: smooth;
        }

        .navbar {
            background-color: #fff;
            border-bottom: 1px solid #ddd;
        }

        .navbar-nav .nav-item .nav-link {
            color: #333 !important;
            font-weight: bold;
            margin: 0 15px;
        }

        .header, .content-section {
            padding: 60px 0;
            text-align: center;
        }

        .header {
            background-color: #f8f9fa;
        }

        .header img {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-weight: 700;
            margin-top: 20px;
        }

        .header p {
            font-size: 1.2em;
            color: #666;
        }

        .btn {
            border-radius: 25px;
            font-size: 1.2em;
            margin: 5px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-primary:hover, .btn-success:hover, .btn-warning:hover, .btn-info:hover, .btn-secondary:hover {
            transform: scale(1.05);
        }

        .content-section:nth-of-type(even) {
            background-color: #f8f9fa;
        }

        .content-section h2 {
            font-weight: 700;
            margin-bottom: 20px;
        }

        .content-section p, .content-section ul {
            color: #666;
        }

        .carousel-item img {
            max-height: 600px;
            object-fit: cover;
            width: 100%;
        }

        .contact-section {
            background-color: #FFA500;
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .contact-section h2 {
            font-weight: 700;
            margin-bottom: 20px;
        }

        .contact-section p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .contact-section a {
            color: white;
            font-weight: bold;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade {
            animation: fadeIn 1.5s ease-out;
        }

        .scroll-section {
            scroll-margin-top: 60px;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                text-align: center;
            }
            .navbar-nav .nav-item {
                margin-bottom: 10px;
            }
            .header img {
                max-width: 120px;
            }
            .btn {
                font-size: 1em;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <a class="navbar-brand" href="#">
            <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" alt="Nutrition System Logo" class="logo" width="50" height="50">
            Nutrition System
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#home">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#contact">Contact</a>
                </li>
            </ul>
        </div>
    </nav>

    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <li data-target="#carouselExampleIndicators" data-slide-to="0" class="active"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="1"></li>
            <li data-target="#carouselExampleIndicators" data-slide-to="2"></li>
        </ol>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="/mnt/data/Screen Shot 2024-06-27 at 10.09.33 PM.png" class="d-block w-100" alt="First slide">
            </div>
            <div class="carousel-item">
                <img src="/mnt/data/Screen Shot 2024-06-27 at 10.09.38 PM.png" class="d-block w-100" alt="Second slide">
            </div>
            <div class="carousel-item">
                <img src="/mnt/data/Screen Shot 2024-06-27 at 10.09.43 PM.png" class="d-block w-100" alt="Third slide">
            </div>
            <div class="carousel-item">
                <img src="/mnt/data/Screen Shot 2024-06-27 at 10.09.50 PM.png" class="d-block w-100" alt="Fourth slide">
            </div>
            <div class="carousel-item">
                <img src="/mnt/data/Screen Shot 2024-06-27 at 10.09.58 PM.png" class="d-block w-100" alt="Fifth slide">
            </div>
        </div>
        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
    
    <div id="home" class="header scroll-section">
        <img src="Green_And_White_Aesthetic_Salad_Vegan_Logo__6_-removebg-preview.png" alt="Nutrition System Logo">
        <h1 class="animate-fade">Welcome to Nutrition System</h1>
        <p class="animate-fade">Nutrition System is your one-stop solution for personalized meal plans based on your health needs. Whether you are managing a condition or looking to optimize your diet, our app helps you choose the best meals for your well-being.</p>
        <div class="animate-fade">
            <a href="login.php" class="btn btn-primary btn-lg">Admin Login</a>
            <a href="user_login.php" class="btn btn-success btn-lg">User Login</a>
            <a href="delivery_login.php" class="btn btn-warning btn-lg">Delivery Login</a>
            <a href="register_user.php" class="btn btn-info btn-lg">User Registration</a>
            <a href="register_delivery.php" class="btn btn-secondary btn-lg">Delivery Registration</a>
        </div>
    </div>
    
    <div id="about" class="content-section scroll-section text-center">
        <h2 class="animate-fade">About Us</h2>
        <p class="animate-fade">Nutrition System is dedicated to helping you maintain a healthy lifestyle through personalized meal plans and expert advice.</p>
    </div>

    <div id="features" class="content-section scroll-section text-center">
        <h2 class="animate-fade">Features</h2>
        <p class="animate-fade">Explore the amazing features of our app:</p>
        <div class="row">
            <div class="col-md-3">
                <img src="your-icon-url" alt="Feature 1">
                <h3>Personalized Meal Plans</h3>
                <p>Get meals tailored to your health needs.</p>
            </div>
            <div class="col-md-3">
                <img src="your-icon-url" alt="Feature 2">
                <h3>Health Condition Management</h3>
                <p>Manage and improve your health conditions.</p>
            </div>
            <div class="col-md-3">
                <img src="your-icon-url" alt="Feature 3">
                <h3>Easy Meal Tracking</h3>
                <p>Track your meals with ease.</p>
            </div>
            <div class="col-md-3">
                <img src="your-icon-url" alt="Feature 4">
                <h3>Admin & Delivery Management</h3>
                <p>Efficiently manage users and deliveries.</p>
            </div>
        </div>
    </div>
    
    <div id="contact" class="contact-section scroll-section">
        <h2 class="animate-fade">Ready to bloom?</h2>
        <p class="animate-fade">Get in touch with us today!</p>
        <p class="animate-fade"><a href="mailto:hello@nutrition-system.com">hello@nutrition-system.com</a></p>
        <p class="animate-fade">+123-456-7890</p>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
