<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/fooddelivery/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container py-5">
        <section class="contact-section">
            <h1 class="text-center mb-4">Contact Us</h1>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-info-section">
                        <h2>Get in Touch</h2>
                        <p>We'd love to hear from you! Whether you have a question about our menu, want to make a reservation, or need assistance with your order, our team is here to help.</p>
                        
                        <div class="contact-details mt-4">
                            <div class="contact-item">
                                <strong>Address:</strong>
                                <p>123 Foodie Street, Culinary District<br>City - 123456</p>
                            </div>
                            
                            <div class="contact-item">
                                <strong>Phone:</strong>
                                <p>+91 98765 43210</p>
                            </div>
                            
                            <div class="contact-item">
                                <strong>Email:</strong>
                                <p>info@thenortheast.com</p>
                            </div>
                            
                            <div class="contact-item">
                                <strong>Hours:</strong>
                                <p>Monday to Sunday<br>11:00 AM to 11:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="contact-form-section">
                        <h2>Send us a Message</h2>
                        <form id="contactForm" action="/fooddelivery/php/controllers/contact_controller.php" method="POST">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone (optional)</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <select class="form-control" id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="general">General Inquiry</option>
                                    <option value="reservation">Make a Reservation</option>
                                    <option value="order">Order Related</option>
                                    <option value="feedback">Feedback</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <style>
    .contact-section {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .contact-section h1 {
        color: #333;
        margin-bottom: 30px;
    }

    .contact-section h2 {
        color: #444;
        margin-bottom: 20px;
    }

    .contact-info-section {
        background-color: #f9f9f9;
        padding: 30px;
        border-radius: 5px;
        height: 100%;
    }

    .contact-item {
        margin-bottom: 20px;
    }

    .contact-item strong {
        display: block;
        color: #e67e22;
        margin-bottom: 5px;
    }

    .contact-item p {
        margin: 0;
        color: #666;
        line-height: 1.5;
    }

    .contact-form-section {
        background-color: #fff;
        padding: 30px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: #444;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    textarea.form-control {
        resize: vertical;
    }

    .btn-primary {
        background-color: #e67e22;
        color: #fff;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }

    .btn-primary:hover {
        background-color: #d35400;
    }
    </style>

    <script>
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const formData = new FormData(this);
        
        // Send form data to the server
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for your message. We will get back to you soon!');
                this.reset();
            } else {
                alert('There was an error sending your message. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('There was an error sending your message. Please try again.');
        });
    });
    </script>
</body>
</html> 