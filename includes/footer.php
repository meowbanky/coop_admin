    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-blue-600 p-2 rounded-lg">
                            <i class="fas fa-university text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">OOUTH COOP</h3>
                            <p class="text-gray-400 text-sm">Cooperative Management System</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Empowering our community through cooperative management and financial services.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="home.php" class="text-gray-400 hover:text-white transition-colors">Dashboard</a></li>
                        <li><a href="employee.php" class="text-gray-400 hover:text-white transition-colors">Employee Management</a></li>
                        <li><a href="payprocess.php" class="text-gray-400 hover:text-white transition-colors">Deductions Processing</a></li>
                        <li><a href="masterReportModern.php" class="text-gray-400 hover:text-white transition-colors">Master Report</a></li>
                        <li><a href="print_member.php" class="text-gray-400 hover:text-white transition-colors">Member Contributions</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Information</h4>
                    <div class="space-y-2 text-sm text-gray-400">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span>Olabisi Onabanjo University, Ago-Iwoye</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <span>+234 (0) 123 456 7890</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2"></i>
                            <span>info@oouthcoop.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-gray-700 mt-8 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="text-gray-400 text-sm">
                        <p>&copy; <?= date('Y') ?> OOUTH COOP. All rights reserved.</p>
                    </div>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <span class="text-gray-400 text-sm">Version 2.0</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-gray-400 text-sm">System Online</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Common JavaScript Functions -->
    <script>
    // Global utility functions
    window.CoopUtils = {
        // Show loading spinner
        showLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.innerHTML = '<div class="loading-spinner mx-auto"></div>';
            }
        },

        // Hide loading spinner
        hideLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.innerHTML = '';
            }
        },

        // Show success message
        showSuccess: function(message, title = 'Success') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        },

        // Show error message
        showError: function(message, title = 'Error') {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                confirmButtonText: 'OK'
            });
        },

        // Show confirmation dialog
        showConfirm: function(message, title = 'Confirm', callback) {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        },

        // Format currency
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-NG', {
                style: 'currency',
                currency: 'NGN'
            }).format(amount);
        },

        // Format date
        formatDate: function(date) {
            return new Date(date).toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        // Debounce function for search
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // AJAX helper
        ajax: function(url, options = {}) {
            const defaults = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                }
            };

            const config = { ...defaults, ...options };

            return fetch(url, config)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .catch(error => {
                    console.error('AJAX Error:', error);
                    this.showError('An error occurred while processing your request.');
                    throw error;
                });
        }
    };

    // Initialize common functionality
    $(document).ready(function() {
        // Add fade-in animation to main content
        $('main').addClass('fade-in');

        // Initialize tooltips
        $('[data-tooltip]').each(function() {
            $(this).attr('title', $(this).data('tooltip'));
        });

        // Auto-hide alerts after 5 seconds
        $('.alert').each(function() {
            const alert = $(this);
            setTimeout(function() {
                alert.fadeOut();
            }, 5000);
        });
    });
    </script>
</body>
</html>
