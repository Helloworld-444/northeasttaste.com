<footer class="footer bg-dark text-light mt-5">
    <div class="container py-5">
        <div class="row">
            <!-- Company Info -->
            <div class="col-md-4 mb-4">
                <h5 class="mb-3">The Northeast</h5>
                <p>Bringing authentic Northeast Indian cuisine to your doorstep. Experience the unique flavors and traditions of the Seven Sisters.</p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-md-4 mb-4">
                <h5 class="mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?= url('php/views/menu.php') ?>" class="text-light text-decoration-none">
                            <i class="fas fa-utensils me-2"></i>Our Menu
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= url('about') ?>" class="text-light text-decoration-none">
                            <i class="fas fa-info-circle me-2"></i>About Us
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= url('contact') ?>" class="text-light text-decoration-none">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="<?= url('php/views/privacy.php') ?>" class="text-light text-decoration-none">
                            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                        </a>
                    </li>
                    <li>
                        <a href="<?= url('php/views/terms.php') ?>" class="text-light text-decoration-none">
                            <i class="fas fa-file-alt me-2"></i>Terms & Conditions
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-md-4 mb-4">
                <h5 class="mb-3">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        123 Main Street, City, State 12345
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        +1 (555) 123-4567
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        info@thenortheast.com
                    </li>
                    <li>
                        <i class="fas fa-clock me-2"></i>
                        Mon-Sun: 11:00 AM - 10:00 PM
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="text-center py-3 border-top border-secondary">
        <p class="mb-0">&copy; <?= date('Y') ?> The Northeast. All rights reserved.</p>
    </div>
</footer>

<!-- Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Send Message to Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= SITE_URL ?>/php/controllers/message_controller.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Message Button -->
<button type="button" class="btn btn-primary message-btn" data-bs-toggle="modal" data-bs-target="#messageModal">
    <i class="fas fa-comment"></i>
</button>

<style>
.message-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
</style>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?= url('js/main.js') ?>"></script>
<!-- Cart JavaScript -->
<script src="<?= url('js/cart.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle message form submission
    const messageForm = document.querySelector('#messageModal form');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'send');
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Message sent successfully!');
                    this.reset();
                    bootstrap.Modal.getInstance(document.getElementById('messageModal')).hide();
                } else {
                    throw new Error(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                alert(error.message);
            });
        });
    }
});
</script>
</body>
</html> 