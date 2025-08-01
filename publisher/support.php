<?php
// File: /publisher/support.php (NEW - Modern Support Page)

require_once __DIR__ . '/init.php';

$publisher_id = $_SESSION['publisher_id'];
$publisher_username = $_SESSION['publisher_username'];
$publisher_email = '';

// Get publisher email
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $publisher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $publisher_email = $row['email'];
}
$stmt->close();

// Get recent tickets if we have a database table for them
$recent_tickets = [];
if($conn->query("SHOW TABLES LIKE 'support_tickets'")->num_rows > 0) {
    $stmt = $conn->prepare("SELECT id, subject, status, priority, created_at, last_updated_at 
                          FROM support_tickets 
                          WHERE user_id = ? 
                          ORDER BY last_updated_at DESC 
                          LIMIT 5");
    $stmt->bind_param("i", $publisher_id);
    $stmt->execute();
    $recent_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get frequently asked questions from the database or use static ones
$faqs = [];
if($conn->query("SHOW TABLES LIKE 'faqs'")->num_rows > 0) {
    $result = $conn->query("SELECT id, question, answer, category FROM faqs WHERE is_publisher = 1 ORDER BY display_order ASC LIMIT 10");
    if($result) {
        while($row = $result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }
} else {
    // Static FAQs as fallback
    $faqs = [
        ['id' => 1, 'question' => 'How do I add a new website to my account?', 
         'answer' => 'To add a new website, go to the "Sites & Zones" page and click on the "Add New Site" button. Fill in your website URL and select the appropriate category. Your site will be reviewed and approved typically within 24-48 hours.', 
         'category' => 'account'],
         
        ['id' => 2, 'question' => 'When and how do I get paid?', 
         'answer' => 'Payments are processed monthly for all earnings from the previous month. You need to have at least $50 in your account to request a withdrawal. You can choose your payment method in the "Payments" section.', 
         'category' => 'payments'],
         
        ['id' => 3, 'question' => 'Why was my website rejected?', 
         'answer' => 'Websites may be rejected if they contain prohibited content, have insufficient traffic, or violate our terms of service. You\'ll receive a specific reason for the rejection in your email. You can contact support for more details.', 
         'category' => 'account'],
         
        ['id' => 4, 'question' => 'How do I implement ads on my website?', 
         'answer' => 'After your site is approved, you can create zones in the "Sites & Zones" section. For each zone, you\'ll get a code snippet that you need to insert in your website where you want the ads to appear.', 
         'category' => 'technical'],
         
        ['id' => 5, 'question' => 'What affects my ad revenue?', 
         'answer' => 'Your revenue depends on factors like the number of impressions, click-through rate (CTR), placement of ads, content quality, user engagement, and geographical location of your audience.', 
         'category' => 'earnings']
    ];
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="fw-bold mb-1">Support Center</h4>
        <p class="text-muted mb-0">Get help with your publisher account</p>
    </div>
    
    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
        <i class="bi bi-plus-lg me-1"></i> Submit New Ticket
    </a>
</div>

<div class="row mb-4">
    <div class="col-lg-8">
        <!-- Support Options Cards -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-headset me-2 text-primary"></i> How Can We Help You?
                </h5>
            </div>
            <div class="card-body pb-2">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="support-card">
                            <div class="support-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="support-content">
                                <h6>Knowledge Base</h6>
                                <p class="text-muted small mb-3">Browse our comprehensive guides and tutorials</p>
                                <a href="https://support.clicterra.com/knowledge-base" target="_blank" class="btn btn-sm btn-outline-primary">
                                    View Articles
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="support-card">
                            <div class="support-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-chat-dots"></i>
                            </div>
                            <div class="support-content">
                                <h6>Live Chat</h6>
                                <p class="text-muted small mb-3">Chat with our support team in real-time</p>
                                <button class="btn btn-sm btn-outline-success" id="startChatBtn">
                                    Start Chat
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="support-card">
                            <div class="support-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-tools"></i>
                            </div>
                            <div class="support-content">
                                <h6>Technical Support</h6>
                                <p class="text-muted small mb-3">Get help with implementation issues</p>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#newTicketModal" data-ticket-type="technical" class="btn btn-sm btn-outline-warning">
                                    Request Help
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="support-card">
                            <div class="support-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-person-video3"></i>
                            </div>
                            <div class="support-content">
                                <h6>Account Manager</h6>
                                <p class="text-muted small mb-3">Speak with your dedicated manager</p>
                                <a href="mailto:accounts@clicterra.com" class="btn btn-sm btn-outline-info">
                                    Contact Manager
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-ticket-perforated me-2 text-primary"></i> Your Recent Tickets
                </h5>
                <?php if(!empty($recent_tickets)): ?>
                <a href="tickets.php" class="btn btn-sm btn-light">
                    View All
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if(!empty($recent_tickets)): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Subject</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th class="pe-4">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_tickets as $ticket): 
                                $status_class = [
                                    'open' => 'success',
                                    'pending' => 'warning',
                                    'closed' => 'secondary',
                                    'resolved' => 'info'
                                ][$ticket['status']] ?? 'primary';
                                
                                $priority_class = [
                                    'low' => 'success',
                                    'medium' => 'warning',
                                    'high' => 'danger',
                                    'urgent' => 'danger'
                                ][$ticket['priority']] ?? 'secondary';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="text-decoration-none text-dark fw-medium">
                                        <?php echo htmlspecialchars($ticket['subject']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?> bg-opacity-10 text-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $priority_class; ?> bg-opacity-10 text-<?php echo $priority_class; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-muted small">
                                    <?php echo date('M d, Y', strtotime($ticket['last_updated_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="bi bi-ticket-perforated"></i>
                    </div>
                    <h6 class="mb-2">No Recent Tickets</h6>
                    <p class="text-muted mb-4">You haven't submitted any support tickets yet</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="bi bi-plus-lg me-1"></i> Submit Your First Ticket
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-question-circle me-2 text-primary"></i> Frequently Asked Questions
                </h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <?php foreach($faqs as $index => $faq): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq-heading-<?php echo $faq['id']; ?>">
                            <button class="accordion-button <?php echo ($index !== 0) ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#faq-collapse-<?php echo $faq['id']; ?>" 
                                    aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" 
                                    aria-controls="faq-collapse-<?php echo $faq['id']; ?>">
                                <?php echo htmlspecialchars($faq['question']); ?>
                                <span class="badge bg-<?php 
                                    echo match($faq['category']) {
                                        'payments' => 'success',
                                        'account' => 'primary',
                                        'technical' => 'danger',
                                        'earnings' => 'warning',
                                        default => 'secondary'
                                    }; 
                                ?> bg-opacity-10 text-<?php 
                                    echo match($faq['category']) {
                                        'payments' => 'success',
                                        'account' => 'primary',
                                        'technical' => 'danger',
                                        'earnings' => 'warning',
                                        default => 'secondary'
                                    }; 
                                ?> ms-2 small">
                                    <?php echo ucfirst($faq['category']); ?>
                                </span>
                            </button>
                        </h2>
                        <div id="faq-collapse-<?php echo $faq['id']; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" 
                             aria-labelledby="faq-heading-<?php echo $faq['id']; ?>" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <?php echo nl2br(htmlspecialchars($faq['answer'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="https://support.clicterra.com/faq" target="_blank" class="btn btn-outline-primary">
                        View All FAQs <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Contact Info Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-envelope me-2 text-primary"></i> Contact Information
                </h5>
            </div>
            <div class="card-body">
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h6 class="mb-1">Email Support</h6>
                            <p class="mb-0">support@clicterra.com</p>
                            <p class="small text-muted">Response within 24 hours</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div class="contact-details">
                            <h6 class="mb-1">Phone Support</h6>
                            <p class="mb-0">+1 (555) 123-4567</p>
                            <p class="small text-muted">Mon-Fri, 9AM-5PM ET</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-discord"></i>
                        </div>
                        <div class="contact-details">
                            <h6 class="mb-1">Publisher Community</h6>
                            <p class="mb-0">Join our Discord community</p>
                            <p class="small text-muted">
                                <a href="https://discord.gg/clicterra" target="_blank">discord.gg/clicterra</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center mt-3">
                    <h6>Support Hours</h6>
                    <p class="mb-1">Monday - Friday: 9AM - 8PM ET</p>
                    <p class="mb-0">Saturday: 10AM - 4PM ET</p>
                    <p class="mb-0">Sunday: Closed</p>
                </div>
            </div>
        </div>
        
        <!-- Self-Help Resources -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-journal-text me-2 text-primary"></i> Self-Help Resources
                </h5>
            </div>
            <div class="card-body">
                <ul class="resource-list">
                    <li>
                        <a href="https://support.clicterra.com/getting-started" target="_blank">
                            <div class="resource-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-rocket-takeoff"></i>
                            </div>
                            <div class="resource-content">
                                <h6>Getting Started Guide</h6>
                                <p class="small text-muted mb-0">Quick setup tutorial</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="https://support.clicterra.com/ad-implementation" target="_blank">
                            <div class="resource-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-code-square"></i>
                            </div>
                            <div class="resource-content">
                                <h6>Ad Implementation</h6>
                                <p class="small text-muted mb-0">Code examples & tutorials</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="https://support.clicterra.com/optimization" target="_blank">
                            <div class="resource-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="resource-content">
                                <h6>Revenue Optimization</h6>
                                <p class="small text-muted mb-0">Tips to maximize earnings</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="https://support.clicterra.com/payment-guide" target="_blank">
                            <div class="resource-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="resource-content">
                                <h6>Payment Guide</h6>
                                <p class="small text-muted mb-0">Understanding payments</p>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a href="https://support.clicterra.com/api-docs" target="_blank">
                            <div class="resource-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-braces"></i>
                            </div>
                            <div class="resource-content">
                                <h6>API Documentation</h6>
                                <p class="small text-muted mb-0">Developer resources</p>
                            </div>
                        </a>
                    </li>
                </ul>
                
                <div class="text-center mt-3">
                    <a href="https://support.clicterra.com" target="_blank" class="btn btn-sm btn-outline-primary">
                        Visit Support Portal
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Status -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity me-2 text-primary"></i> System Status
                </h5>
            </div>
            <div class="card-body">
                <div class="status-item">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ad Serving</span>
                        <span class="badge bg-success">Operational</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Publisher Dashboard</span>
                        <span class="badge bg-success">Operational</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Reporting System</span>
                        <span class="badge bg-success">Operational</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="status-item">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Payment Processing</span>
                        <span class="badge bg-success">Operational</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Last updated: <?php echo date("M d, Y H:i", strtotime("2025-07-19 00:30:00")); ?> UTC
                    </small>
                    <div class="mt-2">
                        <a href="https://status.clicterra.com" target="_blank" class="btn btn-sm btn-outline-secondary">
                            View Status Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-ticket-perforated me-2 text-primary"></i> Submit New Support Ticket
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="ticket-action.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ticket_subject" class="form-label small fw-medium">Subject</label>
                        <input type="text" class="form-control" id="ticket_subject" name="subject" required placeholder="Brief description of your issue">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ticket_type" class="form-label small fw-medium">Ticket Type</label>
                            <select class="form-select" id="ticket_type" name="type" required>
                                <option value="">Select type...</option>
                                <option value="account">Account Related</option>
                                <option value="technical">Technical Support</option>
                                <option value="payment">Payment Issue</option>
                                <option value="ad_quality">Ad Quality</option>
                                <option value="performance">Performance Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ticket_priority" class="form-label small fw-medium">Priority</label>
                            <select class="form-select" id="ticket_priority" name="priority" required>
                                <option value="">Select priority...</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ticket_message" class="form-label small fw-medium">Message</label>
                        <textarea class="form-control" id="ticket_message" name="message" rows="6" required placeholder="Please describe your issue in detail including any error messages, steps to reproduce, URLs affected, etc."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ticket_attachments" class="form-label small fw-medium">Attachments (Optional)</label>
                        <input type="file" class="form-control" id="ticket_attachments" name="attachments[]" multiple>
                        <div class="form-text">You can upload up to 3 files (max 5MB each). Supported formats: JPG, PNG, PDF.</div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cc_email" name="cc_email" value="1">
                            <label class="form-check-label" for="cc_email">
                                Send me a copy of this ticket to my email: <?php echo htmlspecialchars($publisher_email); ?>
                            </label>
                        </div>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i>
                        Our support team typically responds within 24 hours during business hours. For urgent issues, please select "Urgent" priority.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Live Chat Widget Placeholder -->
<div id="chat-widget" class="chat-widget">
    <div class="chat-header">
        <div class="chat-title">
            <i class="bi bi-headset me-2"></i> Live Support
        </div>
        <button class="btn-close-chat" id="closeChatBtn">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="chat-message system">
            <div class="message-content">
                <p>Welcome to Clicterra support chat! Our team will be with you shortly.</p>
                <small class="message-time"><?php echo date("H:i", strtotime("2025-07-19 00:36:01")); ?></small>
            </div>
        </div>
        
        <div class="chat-message system">
            <div class="message-content">
                <p>While you wait, you can describe your issue or question.</p>
                <small class="message-time"><?php echo date("H:i", strtotime("2025-07-19 00:36:01")); ?></small>
            </div>
        </div>
    </div>
    <div class="chat-input">
        <input type="text" id="chatMessage" placeholder="Type your message here..." disabled>
        <button class="btn-send" id="sendMessageBtn" disabled>
            <i class="bi bi-send"></i>
        </button>
    </div>
</div>

<div class="chat-launcher" id="chatLauncher">
    <i class="bi bi-chat-dots"></i>
</div>

<style>
/* Support Cards */
.support-card {
    display: flex;
    align-items: flex-start;
    padding: 1.5rem;
    border-radius: 10px;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    height: 100%;
    border: 1px solid #e9ecef;
}

.support-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.support-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.support-content {
    flex: 1;
}

/* Contact Info */
.contact-info {
    margin-bottom: 1.5rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.25rem;
}

.contact-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.contact-details h6 {
    margin-bottom: 0.25rem;
}

/* Resource List */
.resource-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.resource-list li {
    margin-bottom: 1rem;
}

.resource-list li:last-child {
    margin-bottom: 0;
}

.resource-list li a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding: 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.resource-list li a:hover {
    background-color: #f8f9fa;
}

.resource-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

/* Status Items */
.status-item {
    margin-bottom: 1.25rem;
}

.status-item:last-child {
    margin-bottom: 0;
}

/* Empty State */
.empty-state-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background-color: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Chat Widget */
.chat-widget {
    position: fixed;
    bottom: 80px;
    right: 20px;
    width: 350px;
    height: 450px;
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    overflow: hidden;
    display: none;
    border: 1px solid var(--border-color);
}

.chat-header {
    padding: 1rem;
    background-color: var(--primary);
    color: white;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-title {
    display: flex;
    align-items: center;
}

.btn-close-chat {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}

.chat-body {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.chat-message {
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
}

.chat-message.sent {
    justify-content: flex-end;
}

.chat-message.received .message-content,
.chat-message.system .message-content {
    background-color: #f0f2f5;
    border-radius: 18px 18px 18px 0;
}

.chat-message.sent .message-content {
    background-color: var(--primary);
    color: white;
    border-radius: 18px 18px 0 18px;
}

.message-content {
    padding: 0.75rem 1rem;
    max-width: 80%;
    word-wrap: break-word;
}

.message-content p {
    margin: 0;
}

.message-time {
    display: block;
    font-size: 0.7rem;
    margin-top: 0.25rem;
    opacity: 0.8;
}

.chat-input {
    display: flex;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-top: 1px solid var(--border-color);
}

.chat-input input {
    flex: 1;
    border: 1px solid #e9ecef;
    border-radius: 20px;
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
    outline: none;
}

.btn-send {
    background-color: var(--primary);
    color: white;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.chat-launcher {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 60px;
    height: 60px;
    background-color: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 999;
    transition: all 0.3s ease;
}

.chat-launcher:hover {
    transform: scale(1.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Live Chat Widget functionality
    const chatLauncher = document.getElementById('chatLauncher');
    const chatWidget = document.getElementById('chat-widget');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const startChatBtn = document.getElementById('startChatBtn');
    const chatBody = document.getElementById('chatBody');
    const chatMessage = document.getElementById('chatMessage');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    
    function toggleChat() {
        if (chatWidget.style.display === 'flex') {
            chatWidget.style.display = 'none';
        } else {
            chatWidget.style.display = 'flex';
            simulateChatAgent();
        }
    }
    
    if (chatLauncher) {
        chatLauncher.addEventListener('click', toggleChat);
    }
    
    if (closeChatBtn) {
        closeChatBtn.addEventListener('click', function() {
            chatWidget.style.display = 'none';
        });
    }
    
    if (startChatBtn) {
        startChatBtn.addEventListener('click', function() {
            toggleChat();
        });
    }
    
    function simulateChatAgent() {
        // After 2 seconds, show agent joining
        setTimeout(() => {
            const agentMessage = document.createElement('div');
            agentMessage.className = 'chat-message received';
            agentMessage.innerHTML = `
                <div class="message-content">
                    <p>Hi ${<?php echo json_encode($publisher_username); ?>}! I'm Sarah from Clicterra support. How can I assist you today?</p>
                    <small class="message-time">${getCurrentTime()}</small>
                </div>
            `;
            chatBody.appendChild(agentMessage);
            chatBody.scrollTop = chatBody.scrollHeight;
            
            // Enable chat input
            chatMessage.disabled = false;
            sendMessageBtn.disabled = false;
            chatMessage.focus();
        }, 2000);
    }
    
    // Handle sending messages
    function sendMessage() {
        const message = chatMessage.value.trim();
        if (message === '') return;
        
        // Add user message
        const userMessage = document.createElement('div');
        userMessage.className = 'chat-message sent';
        userMessage.innerHTML = `
            <div class="message-content">
                <p>${message}</p>
                <small class="message-time">${getCurrentTime()}</small>
            </div>
        `;
        chatBody.appendChild(userMessage);
        
        // Clear input
        chatMessage.value = '';
        chatBody.scrollTop = chatBody.scrollHeight;
        
        // Simulate typing
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'chat-message received';
        typingIndicator.id = 'typing-indicator';
        typingIndicator.innerHTML = `
            <div class="message-content">
                <p>Sarah is typing...</p>
            </div>
        `;
        chatBody.appendChild(typingIndicator);
        chatBody.scrollTop = chatBody.scrollHeight;
        
        // Simulate response after delay
        setTimeout(() => {
            // Remove typing indicator
            document.getElementById('typing-indicator')?.remove();
            
            // Add agent response
            const agentMessage = document.createElement('div');
            agentMessage.className = 'chat-message received';
            agentMessage.innerHTML = `
                <div class="message-content">
                    <p>Thanks for your message! This is a demo chat interface. In the actual application, our support team would respond to your inquiry here.</p>
                    <small class="message-time">${getCurrentTime()}</small>
                </div>
            `;
            chatBody.appendChild(agentMessage);
            chatBody.scrollTop = chatBody.scrollHeight;
        }, 2000);
    }
    
    if (sendMessageBtn) {
        sendMessageBtn.addEventListener('click', sendMessage);
    }
    
    if (chatMessage) {
        chatMessage.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    // Ticket type selection in modal
    const ticketTypeSelect = document.getElementById('ticket_type');
    const prioritySelect = document.getElementById('ticket_priority');
    
    document.querySelectorAll('[data-ticket-type]').forEach(element => {
        element.addEventListener('click', function() {
            const ticketType = this.getAttribute('data-ticket-type');
            if (ticketTypeSelect) {
                ticketTypeSelect.value = ticketType;
                
                // Set appropriate default priority based on type
                if (prioritySelect) {
                    if (ticketType === 'technical' || ticketType === 'payment') {
                        prioritySelect.value = 'high';
                    } else {
                        prioritySelect.value = 'medium';
                    }
                }
            }
        });
    });
    
    // Animate stats on page load
    const animateElements = document.querySelectorAll('.support-card, .contact-item, .resource-list li, .status-item');
    animateElements.forEach((element, index) => {
        element.style.opacity = 0;
        element.style.transform = 'translateY(10px)';
        setTimeout(() => {
            element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            element.style.opacity = 1;
            element.style.transform = 'translateY(0)';
        }, 100 * index);
    });
    
    // Helper function to get current time for chat
    function getCurrentTime() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>