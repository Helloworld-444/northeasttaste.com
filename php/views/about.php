<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/fooddelivery/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container py-5">
        <section class="about-section">
            <h1 class="text-center mb-4">About The Northeast</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <h2>Our Story</h2>
                    <p>Welcome to The Northeast, your gateway to authentic Northeast Indian cuisine. Our journey began with a simple mission: to bring the rich, diverse flavors of Northeast India to your table.</p>
                    
                    <p>Each dish we serve is crafted with traditional recipes passed down through generations, using authentic ingredients and cooking methods that preserve the true essence of Northeast Indian cooking.</p>
                </div>
                
                <div class="col-md-6">
                    <h2>Our Cuisine</h2>
                    <p>We specialize in dishes from all eight northeastern states of India:</p>
                    <ul>
                        <li>Manipuri cuisine - known for its unique blend of spices and fermented ingredients</li>
                        <li>Assamese delicacies - featuring fresh fish and traditional herbs</li>
                        <li>Naga specialties - famous for their hot chilies and smoked meats</li>
                        <li>Mizoram dishes - characterized by their simplicity and bold flavors</li>
                        <li>Meghalaya favorites - incorporating local ingredients and tribal recipes</li>
                        <li>Tripura specialties - blending Bengali and tribal influences</li>
                        <li>Sikkim cuisine - reflecting Tibetan and Nepalese influences</li>
                        <li>Arunachal Pradesh dishes - showcasing unique tribal cooking methods</li>
                    </ul>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-12">
                    <h2>Our Commitment</h2>
                    <p>At The Northeast, we are committed to:</p>
                    <ul>
                        <li>Using authentic ingredients sourced directly from Northeast India</li>
                        <li>Maintaining the traditional cooking methods and recipes</li>
                        <li>Providing excellent service and a warm, welcoming atmosphere</li>
                        <li>Supporting local communities and sustainable farming practices</li>
                        <li>Educating our customers about Northeast Indian culture and cuisine</li>
                    </ul>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-12">
                    <h2>Visit Us</h2>
                    <p>We invite you to experience the authentic flavors of Northeast India. Whether you're familiar with our cuisine or trying it for the first time, our friendly staff is here to guide you through our menu and ensure you have a memorable dining experience.</p>
                    
                    <div class="contact-info mt-4">
                        <p><strong>Address:</strong> 123 Foodie Street, Culinary District, City - 123456</p>
                        <p><strong>Phone:</strong> +91 98765 43210</p>
                        <p><strong>Email:</strong> info@thenortheast.com</p>
                        <p><strong>Hours:</strong> Monday to Sunday, 11:00 AM to 11:00 PM</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <style>
    .about-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .about-section h1 {
        color: #333;
        margin-bottom: 30px;
    }

    .about-section h2 {
        color: #444;
        margin-top: 20px;
        margin-bottom: 15px;
    }

    .about-section p {
        line-height: 1.6;
        color: #666;
    }

    .about-section ul {
        list-style-type: none;
        padding-left: 0;
    }

    .about-section ul li {
        margin-bottom: 10px;
        padding-left: 20px;
        position: relative;
    }

    .about-section ul li:before {
        content: "•";
        color: #e67e22;
        position: absolute;
        left: 0;
    }

    .contact-info {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 5px;
    }

    .contact-info p {
        margin-bottom: 10px;
    }
    </style>
</body>
</html> 