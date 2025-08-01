<?php
// File: /index.php (Landing Page for Clicterra.com)

// Get current year for copyright
$current_year = date('Y');

// Simple function to check if user is logged in (can be expanded)
function is_logged_in() {
    return isset($_SESSION['publisher_id']);
}

// Testimonial data
$testimonials = [
    [
        'name' => 'Sarah Johnson',
        'position' => 'Blog Owner',
        'image' => 'assets/images/testimonials/user1.jpg',
        'content' => 'Clicterra has transformed how I monetize my blog. Their ad placements are non-intrusive, and I\'ve seen a 40% increase in revenue compared to other platforms.',
        'stars' => 5
    ],
    [
        'name' => 'Michael Chen',
        'position' => 'Media Publisher',
        'image' => 'assets/images/testimonials/user2.jpg',
        'content' => 'The reporting dashboard provides insights I never had before. With Clicterra, I can optimize my ad placements based on real-time data and maximize earnings.',
        'stars' => 5
    ],
    [
        'name' => 'Emma Rodriguez',
        'position' => 'News Site Owner',
        'image' => 'assets/images/testimonials/user3.jpg',
        'content' => 'What stands out about Clicterra is their support team. They helped me optimize my site\'s layout, resulting in higher CTR and better user engagement.',
        'stars' => 4
    ]
];

// Stats data
$stats = [
    ['value' => '10K+', 'label' => 'Publishers'],
    ['value' => '500M+', 'label' => 'Monthly Impressions'],
    ['value' => '99.9%', 'label' => 'Uptime'],
    ['value' => '24/7', 'label' => 'Support']
];

// Features data
$features = [
    [
        'icon' => 'bi-graph-up-arrow',
        'title' => 'Higher Revenue',
        'description' => 'Our advanced algorithms ensure you earn the highest possible revenue for each ad impression.',
        'color' => 'primary'
    ],
    [
        'icon' => 'bi-shield-check',
        'title' => 'Brand Safety',
        'description' => 'We ensure all ads are high-quality and safe, protecting your brand and audience trust.',
        'color' => 'success'
    ],
    [
        'icon' => 'bi-speedometer2',
        'title' => 'Fast Integration',
        'description' => 'Simple one-time setup with a single code snippet - no technical expertise required.',
        'color' => 'danger'
    ],
    [
        'icon' => 'bi-wallet2',
        'title' => 'Timely Payments',
        'description' => 'Choose from multiple payment methods with guaranteed on-time monthly payments.',
        'color' => 'warning'
    ],
    [
        'icon' => 'bi-phone',
        'title' => 'Mobile Optimized',
        'description' => 'All ad formats are fully responsive and optimized for mobile viewing experiences.',
        'color' => 'info'
    ],
    [
        'icon' => 'bi-bar-chart',
        'title' => 'Detailed Analytics',
        'description' => 'Comprehensive real-time reporting to track performance and optimize your strategy.',
        'color' => 'secondary'
    ]
];

// Ad formats data
$ad_formats = [
    [
        'name' => 'Display Ads',
        'icon' => 'bi-display',
        'description' => 'Traditional banner ads in various sizes that integrate seamlessly with your content.'
    ],
    [
        'name' => 'Native Ads',
        'icon' => 'bi-newspaper',
        'description' => 'Ads that match the look, feel and function of your website content.'
    ],
    [
        'name' => 'Video Ads',
        'icon' => 'bi-play-circle',
        'description' => 'Engage users with high-performing video ad formats across all devices.'
    ],
    [
        'name' => 'Interstitial',
        'icon' => 'bi-window',
        'description' => 'Full-screen ads that appear between content pages for maximum visibility.'
    ]
];

// FAQ data
$faqs = [
    [
        'question' => 'How do I get started with Clicterra?',
        'answer' => 'Getting started is easy! Simply sign up for a publisher account, add your website for review, and once approved, you can create ad zones and implement our ad code on your site. Our team is always available to help you through the process.'
    ],
    [
        'question' => 'What are the payment terms?',
        'answer' => 'We offer Net-30 payment terms with a minimum payout threshold of $50. Payments are processed monthly between the 1st and 10th for the previous month\'s earnings. We support multiple payment methods including PayPal, bank transfers, and cryptocurrency.'
    ],
    [
        'question' => 'How much can I earn with Clicterra?',
        'answer' => 'Earnings vary based on factors like traffic volume, geographic location of your audience, niche, and ad placement. Our publishers typically see 20-40% higher earnings compared to other platforms due to our optimized ad delivery and premium advertiser relationships.'
    ],
    [
        'question' => 'What type of websites do you accept?',
        'answer' => 'We accept a wide range of websites with original content and genuine traffic. Sites must be in compliance with our content policies (no adult, illegal, or copyright-infringing content). Websites should have a minimum of 10,000 monthly page views for optimal performance.'
    ],
    [
        'question' => 'Do you offer a referral program?',
        'answer' => 'Yes! Our referral program allows you to earn 5% commission on the lifetime earnings of publishers you refer to our platform. There\'s no limit to how many publishers you can refer or how much you can earn through referrals.'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clicterra - Premium Publisher Monetization Platform</title>
    <meta name="description" content="Maximize your website revenue with Clicterra's advanced ad monetization platform for publishers. Higher CPMs, timely payments, and exceptional support.">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    
    <!-- Custom CSS -->
    <style>
        /* Base styles */
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4ade80;
            --danger: #f43f5e;
            --warning: #fbbf24;
            --info: #38bdf8;
            --light: #f8fafc;
            --dark: #0f172a;
            --gray: #64748b;
            --bg-light: #f5f7fa;
            --border-color: #e2e8f0;
            --text-color: #334155;
            --text-muted: #64748b;
            --header-height: 80px;
            --footer-height: 80px;
            --transition: all 0.3s ease;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 10px 20px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0.05);
            --shadow-lg: 0 15px 30px rgba(0,0,0,0.1), 0 5px 15px rgba(0,0,0,0.05);
            --border-radius-sm: 0.375rem;
            --border-radius: 0.5rem;
            --border-radius-lg: 0.75rem;
        }

        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            background-color: #ffffff;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--dark);
            font-weight: 700;
        }

        /* Utility Classes */
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .bg-light-subtle {
            background-color: var(--bg-light);
        }

        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-padding {
            padding: 100px 0;
        }

        .section-padding-sm {
            padding: 60px 0;
        }

        .section-title {
            margin-bottom: 60px;
            text-align: center;
        }

        .section-title h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .section-title p {
            max-width: 700px;
            margin: 0 auto;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Button Styles */
        .btn {
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .btn-lg {
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
        }

        /* Navbar */
        .navbar {
            padding: 1rem 0;
            background-color: transparent;
            transition: var(--transition);
        }

        .navbar.scrolled {
            background-color: #fff;
            box-shadow: var(--shadow-sm);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            color: var(--dark);
        }

        .navbar-brand span {
            color: var(--primary);
        }

        .navbar-brand img {
            height: 40px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark);
            margin: 0 0.5rem;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-link::after {
            content: "";
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary);
            transition: var(--transition);
        }

        .nav-link:hover::after {
            width: 100%;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 180px 0 120px;
            background-color: var(--bg-light);
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-text {
            font-size: 1.2rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
        }

        .hero-shape {
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        .hero-shape-2 {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background-color: rgba(74, 222, 128, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        /* Features */
        .feature-item {
            padding: 2rem;
            border-radius: var(--border-radius);
            background-color: #fff;
            box-shadow: var(--shadow-sm);
            height: 100%;
            transition: var(--transition);
        }

        .feature-item:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-md);
        }

        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-text {
            color: var(--text-muted);
            margin-bottom: 0;
        }

        /* How It Works */
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 3rem;
        }

        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: var(--primary);
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 50%;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .step-text {
            color: var(--text-muted);
            margin-bottom: 0;
        }

        /* Ad Formats */
        .ad-format-card {
            padding: 2rem;
            border-radius: var(--border-radius);
            background-color: #fff;
            box-shadow: var(--shadow-sm);
            height: 100%;
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .ad-format-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .ad-format-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-md);
        }

        .ad-format-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .ad-format-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .ad-format-text {
            color: var(--text-muted);
            margin-bottom: 0;
        }

        /* Stats Section */
        .stats-section {
            position: relative;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 80px 0;
        }

        .stats-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;charset=utf8,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"%3E%3Cpath fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,181.3C384,203,480,213,576,197.3C672,181,768,139,864,122.7C960,107,1056,117,1152,138.7C1248,160,1344,192,1392,208L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"%3E%3C/path%3E%3C/svg%3E');
            background-size: cover;
            opacity: 0.8;
        }

        .stats-item {
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .stats-value {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stats-label {
            font-size: 1.2rem;
            opacity: 0.8;
        }

        /* Testimonials */
        .testimonial-card {
            background-color: #fff;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            height: 100%;
            transition: var(--transition);
        }

        .testimonial-card:hover {
            box-shadow: var(--shadow-md);
        }

        .testimonial-content {
            position: relative;
            padding-top: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-muted);
            font-style: italic;
        }

        .testimonial-content::before {
            content: """;
            position: absolute;
            top: -20px;
            left: -10px;
            font-size: 5rem;
            color: rgba(67, 97, 238, 0.1);
            font-family: Georgia, serif;
            line-height: 1;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .testimonial-author-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }

        .testimonial-author-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .testimonial-author-position {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .testimonial-stars {
            color: #fbbf24;
            margin-top: 0.5rem;
        }

        /* Swiper Customization */
        .swiper {
            padding-bottom: 60px;
        }

        .swiper-pagination-bullet {
            background-color: var(--primary);
            opacity: 0.5;
            width: 10px;
            height: 10px;
        }

        .swiper-pagination-bullet-active {
            opacity: 1;
            width: 30px;
            border-radius: 5px;
        }

        /* FAQ Section */
        .accordion-item {
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }

        .accordion-header {
            position: relative;
        }

        .accordion-button {
            font-weight: 600;
            padding: 1.25rem;
            background-color: #fff;
            box-shadow: none;
        }

        .accordion-button:not(.collapsed) {
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: var(--border-color);
        }

        .accordion-body {
            color: var(--text-muted);
            padding: 1.25rem;
        }

        /* CTA Section */
        .cta-section {
            position: relative;
            background-color: var(--bg-light);
            padding: 100px 0;
            overflow: hidden;
        }

        .cta-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta-text {
            max-width: 700px;
            margin: 0 auto 2rem;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .cta-shape {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        .cta-shape-2 {
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 200px;
            height: 200px;
            background-color: rgba(74, 222, 128, 0.1);
            border-radius: 50%;
            z-index: 1;
        }

        /* Footer */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 80px 0 20px;
        }

        .footer-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .footer-text {
            color: rgba(255, 255, 255, 0.7);
            max-width: 300px;
            margin-bottom: 1.5rem;
        }

        .footer-title {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .footer-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background-color: var(--primary);
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links li a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links li a:hover {
            color: white;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transition: var(--transition);
            text-decoration: none;
        }

        .social-link:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 3rem;
        }

        .footer-bottom-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* Floating CTA */
        .floating-cta {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            font-weight: 600;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            pointer-events: none;
        }

        .floating-cta.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }

        .floating-cta:hover {
            background-color: var(--primary-dark);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .floating-cta i {
            margin-right: 0.5rem;
        }

        /* Back To Top */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            text-decoration: none;
            opacity: 0;
            transform: translateY(20px);
            transition: var(--transition);
            pointer-events: none;
        }

        .back-to-top.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }

        .back-to-top:hover {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-3px);
        }

        /* Animations on Scroll */
        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .fade-up.appear {
            opacity: 1;
            transform: translateY(0);
        }

        /* Media Queries */
        @media (max-width: 1199.98px) {
            .hero-title {
                font-size: 3rem;
            }
            .section-padding {
                padding: 80px 0;
            }
        }

        @media (max-width: 991.98px) {
            .hero-title {
                font-size: 2.5rem;
            }
            .hero-section {
                padding: 140px 0 80px;
            }
            .hero-image {
                margin-top: 3rem;
            }
            .section-padding {
                padding: 70px 0;
            }
            .step-item {
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .hero-title {
                font-size: 2.2rem;
            }
            .section-padding {
                padding: 60px 0;
            }
            .section-title h2 {
                font-size: 2rem;
            }
            .stats-value {
                font-size: 2.5rem;
            }
            .cta-title {
                font-size: 2rem;
            }
            .hero-buttons {
                flex-direction: column;
                align-items: flex-start;
            }
            .hero-buttons .btn {
                margin-bottom: 1rem;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            .hero-title {
                font-size: 1.8rem;
            }
            .hero-text {
                font-size: 1rem;
            }
            .section-padding {
                padding: 50px 0;
            }
            .section-title h2 {
                font-size: 1.8rem;
            }
            .stats-item {
                margin-bottom: 2rem;
            }
            .stats-value {
                font-size: 2rem;
            }
            .cta-title {
                font-size: 1.8rem;
            }
            .footer {
                padding: 50px 0 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <span>Clic</span>terra
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto me-4 my-3 my-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#ad-formats">Ad Formats</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#faq">FAQ</a>
                    </li>
                </ul>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="publisher/login.php">Login</a>
                    <a class="btn btn-primary" href="publisher/signup.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">Maximize Your Website Revenue with Premium Ad Solutions</h1>
                    <p class="hero-text">Join thousands of successful publishers using Clicterra to monetize their traffic with high-quality ads and industry-leading CPMs.</p>
                    <div class="hero-buttons">
                        <a href="publisher/signup.php" class="btn btn-primary btn-lg">Get Started <i class="bi bi-arrow-right-short"></i></a>
                        <a href="#how-it-works" class="btn btn-outline-primary btn-lg">Learn More</a>
                    </div>
                </div>
                <div class="col-lg-6 hero-image">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjQwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIi8+PHBhdGggZD0iTTI1MCAxMjVoMzAwdjE1MGgtMzAweiIgZmlsbD0iI2ZmZiIgc3Ryb2tlPSIjZTJlOGYwIi8+PHJlY3QgeD0iMjcwIiB5PSIxNTUiIHdpZHRoPSIxMDAiIGhlaWdodD0iOTAiIGZpbGw9IiNmOGZhZmMiLz48cmVjdCB4PSIzOTAiIHk9IjE1NSIgd2lkdGg9IjE0MCIgaGVpZ2h0PSI5MCIgZmlsbD0icmdiYSg2NywgOTcsIDIzOCwgMC4xKSIvPjxwYXRoIGQ9Ik01MCAxMjVoMTgwdjI1MEg1MHoiIGZpbGw9IiNmZmYiIHN0cm9rZT0iI2UyZThmMCIvPjxyZWN0IHg9IjcwIiB5PSIxNTUiIHdpZHRoPSIxNDAiIGhlaWdodD0iMjAiIGZpbGw9IiNlZWYyZmYiLz48cmVjdCB4PSI3MCIgeT0iMTg1IiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjEwIiBmaWxsPSIjZTJlOGYwIi8+PHJlY3QgeD0iNzAiIHk9IjIwNSIgd2lkdGg9IjE0MCIgaGVpZ2h0PSIxMCIgZmlsbD0iI2UyZThmMCIvPjxyZWN0IHg9IjcwIiB5PSIyMjUiIHdpZHRoPSIxNDAiIGhlaWdodD0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48cmVjdCB4PSI3MCIgeT0iMjQ1IiB3aWR0aD0iODAiIGhlaWdodD0iMTAiIGZpbGw9IiNlMmU4ZjAiLz48cmVjdCB4PSI3MCIgeT0iMjc1IiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjMwIiBmaWxsPSJyZ2JhKDY3LCA5NywgMjM4LCAwLjgpIiByeD0iNCIvPjxyZWN0IHg9IjI1MCIgeT0iMjk1IiB3aWR0aD0iMzAwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjZmZmIiBzdHJva2U9IiNlMmU4ZjAiLz48cGF0aCBkPSJNMjcwIDMwNXYyNHMyIDQ3IDYwIDM3YzU4LTEwIDcwLTEwIDEzMC00IDYwIDYgNjgtMzMgNjgtMzNWMzA1eiIgZmlsbD0icmdiYSg2NywgOTcsIDIzOCwgMC41KSIvPjwvc3ZnPg==" alt="Clicterra Dashboard Preview" class="img-fluid">
                </div>
            </div>
        </div>
        <div class="hero-shape"></div>
        <div class="hero-shape-2"></div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <?php foreach($stats as $stat): ?>
                <div class="col-md-3 col-6 mb-4 mb-md-0">
                    <div class="stats-item">
                        <div class="stats-value"><?php echo $stat['value']; ?></div>
                        <div class="stats-label"><?php echo $stat['label']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose <span class="text-gradient">Clicterra</span>?</h2>
                <p>Our platform combines cutting-edge technology with premium advertiser relationships to maximize your revenue while ensuring a great user experience.</p>
            </div>
            
            <div class="row">
                <?php foreach($features as $feature): ?>
                <div class="col-lg-4 col-md-6 mb-4 fade-up">
                    <div class="feature-item">
                        <div class="feature-icon bg-<?php echo $feature['color']; ?> bg-opacity-10 text-<?php echo $feature['color']; ?>">
                            <i class="bi <?php echo $feature['icon']; ?>"></i>
                        </div>
                        <h3 class="feature-title"><?php echo $feature['title']; ?></h3>
                        <p class="feature-text"><?php echo $feature['description']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section-padding bg-light-subtle" id="how-it-works">
        <div class="container">
            <div class="section-title">
                <h2>How <span class="text-gradient">Clicterra</span> Works</h2>
                <p>Get started in minutes with our simple onboarding process and start earning revenue right away.</p>
            </div>
            
            <div class="row align-items-center">
                <div class="col-lg-6 fade-up">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3 class="step-title">Create Your Account</h3>
                            <p class="step-text">Sign up for free and complete your profile with basic information about your website and audience.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3 class="step-title">Add Your Website</h3>
                            <p class="step-text">Submit your website for review. Once approved, you can create ad zones for different placements.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Implement Ad Codes</h3>
                            <p class="step-text">Add our simple code snippets to your website where you want ads to appear.</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3 class="step-title">Earn and Optimize</h3>
                            <p class="step-text">Monitor performance in real-time and receive monthly payments for your earnings.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 fade-up">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAwIiBoZWlnaHQ9IjUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjhmOWZhIiByeD0iMjAiLz48Y2lyY2xlIGN4PSIzMDAiIGN5PSIxMjAiIHI9IjUwIiBmaWxsPSJyZ2JhKDY3LCA5NywgMjM4LCAwLjIpIi8+PHJlY3QgeD0iMTAwIiB5PSIyMDAiIHdpZHRoPSI0MDAiIGhlaWdodD0iNjAiIGZpbGw9IiNmZmYiIHN0cm9rZT0iI2UyZThmMCIgcng9IjgiLz48Y2lyY2xlIGN4PSIxMzAiIGN5PSIyMzAiIHI9IjIwIiBmaWxsPSJyZ2JhKDY3LCA5NywgMjM4LCAwLjEpIi8+PHJlY3QgeD0iMTYwIiB5PSIyMTUiIHdpZHRoPSIxMjAiIGhlaWdodD0iOCIgZmlsbD0iI2UyZThmMCIgcng9IjQiLz48cmVjdCB4PSIxNjAiIHk9IjIzNSIgd2lkdGg9IjIyMCIgaGVpZ2h0PSI4IiBmaWxsPSIjZTJlOGYwIiByeD0iNCIvPjxyZWN0IHg9IjQyMCIgeT0iMjIyIiB3aWR0aD0iNjAiIGhlaWdodD0iMTYiIGZpbGw9IiM0MzYxZWUiIHJ4PSI4Ii8+PHJlY3QgeD0iMTAwIiB5PSIyODAiIHdpZHRoPSI0MDAiIGhlaWdodD0iNjAiIGZpbGw9IiNmZmYiIHN0cm9rZT0iI2UyZThmMCIgcng9IjgiLz48Y2lyY2xlIGN4PSIxMzAiIGN5PSIzMTAiIHI9IjIwIiBmaWxsPSJyZ2JhKDc0LCAyMjIsIDEyOCwgMC4xKSIvPjxyZWN0IHg9IjE2MCIgeT0iMjk1IiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjgiIGZpbGw9IiNlMmU4ZjAiIHJ4PSI0Ii8+PHJlY3QgeD0iMTYwIiB5PSIzMTUiIHdpZHRoPSIyMDAiIGhlaWdodD0iOCIgZmlsbD0iI2UyZThmMCIgcng9IjQiLz48cmVjdCB4PSI0MjAiIHk9IjMwMiIgd2lkdGg9IjYwIiBoZWlnaHQ9IjE2IiBmaWxsPSIjNGFkZTgwIiByeD0iOCIvPjxyZWN0IHg9IjEwMCIgeT0iMzYwIiB3aWR0aD0iNDAwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZmZmIiBzdHJva2U9IiNlMmU4ZjAiIHJ4PSI4Ii8+PGNpcmNsZSBjeD0iMTMwIiBjeT0iMzkwIiByPSIyMCIgZmlsbD0icmdiYSg1NiwgMTg5LCAyNDgsIDAuMSkiLz48cmVjdCB4PSIxNjAiIHk9IjM3NSIgd2lkdGg9IjEzMCIgaGVpZ2h0PSI4IiBmaWxsPSIjZTJlOGYwIiByeD0iNCIvPjxyZWN0IHg9IjE2MCIgeT0iMzk1IiB3aWR0aD0iMTgwIiBoZWlnaHQ9IjgiIGZpbGw9IiNlMmU4ZjAiIHJ4PSI0Ii8+PHJlY3QgeD0iNDIwIiB5PSIzODIiIHdpZHRoPSI2MCIgaGVpZ2h0PSIxNiIgZmlsbD0iIzM4YmRmOCIgcng9IjgiLz48cGF0aCBkPSJNMjUwIDQwbDIwLTIwIDIwIDIwbTAtNDB2NDAiIHN0cm9rZT0iIzQzNjFlZSIgc3Ryb2tlLXdpZHRoPSI0IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiIGZpbGw9Im5vbmUiLz48cGF0aCBkPSJNMjkwIDE2MGwyMC0yMCAyMCAyMG0wLTQwdjQwIiBzdHJva2U9IiM0MzYxZWUiIHN0cm9rZS13aWR0aD0iNCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBmaWxsPSJub25lIi8+PC9zdmc+" alt="How Clicterra Works" class="img-fluid rounded-4 shadow-sm">
                </div>
            </div>
        </div>
    </section>

    <!-- Ad Formats Section -->
    <section class="section-padding" id="ad-formats">
        <div class="container">
            <div class="section-title">
                <h2>Our <span class="text-gradient">Ad Formats</span></h2>
                <p>Choose from a variety of ad formats to maximize your revenue potential while maintaining a positive user experience.</p>
            </div>
            
            <div class="row">
                <?php foreach($ad_formats as $format): ?>
                <div class="col-md-6 col-lg-3 mb-4 fade-up">
                    <div class="ad-format-card">
                        <div class="ad-format-icon">
                            <i class="bi <?php echo $format['icon']; ?>"></i>
                        </div>
                        <h3 class="ad-format-title"><?php echo $format['name']; ?></h3>
                        <p class="ad-format-text"><?php echo $format['description']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="publisher/signup.php" class="btn btn-primary btn-lg">Get Started Now</a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section-padding bg-light-subtle" id="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Our <span class="text-gradient">Publishers Say</span></h2>
                <p>Join thousands of satisfied publishers who have increased their revenue with Clicterra.</p>
            </div>
            
            <div class="swiper testimonial-swiper fade-up">
                <div class="swiper-wrapper">
                    <?php foreach($testimonials as $testimonial): ?>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <?php echo $testimonial['content']; ?>
                            </div>
                            <div class="testimonial-author">
                                <img 
                                    src="data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%3E%3Ccircle%20fill%3D%22%234361ee%22%20cx%3D%2230%22%20cy%3D%2230%22%20r%3D%2230%22%2F%3E%3Ctext%20fill%3D%22%23ffffff%22%20font-family%3D%22Arial%22%20font-size%3D%2224%22%20font-weight%3D%22bold%22%20text-anchor%3D%22middle%22%20x%3D%2230%22%20y%3D%2238%22%3E<?php echo substr($testimonial['name'], 0, 1); ?>%3C%2Ftext%3E%3C%2Fsvg%3E" 
                                    alt="<?php echo $testimonial['name']; ?>" 
                                    class="testimonial-author-img"
                                >
                                <div>
                                    <div class="testimonial-author-name"><?php echo $testimonial['name']; ?></div>
                                    <div class="testimonial-author-position"><?php echo $testimonial['position']; ?></div>
                                    <div class="testimonial-stars">
                                        <?php for($i = 0; $i < $testimonial['stars']; $i++): ?>
                                            <i class="bi bi-star-fill"></i>
                                        <?php endfor; ?>
                                        <?php for($i = $testimonial['stars']; $i < 5; $i++): ?>
                                            <i class="bi bi-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section-padding" id="faq">
        <div class="container">
            <div class="section-title">
                <h2>Frequently Asked <span class="text-gradient">Questions</span></h2>
                <p>Find answers to the most common questions about Clicterra and our advertising platform.</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10 fade-up">
                    <div class="accordion" id="faqAccordion">
                        <?php foreach($faqs as $index => $faq): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                    <?php echo $faq['question']; ?>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <?php echo $faq['answer']; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 fade-up">
                <p>Don't see your question here?</p>
                <a href="#" class="btn btn-outline-primary">Contact Support</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content fade-up">
                <h2 class="cta-title">Ready to <span class="text-gradient">Boost Your Revenue</span>?</h2>
                <p class="cta-text">Join thousands of publishers already using Clicterra to monetize their websites effectively. Sign up today and start earning more from your traffic.</p>
                <a href="publisher/signup.php" class="btn btn-primary btn-lg">Sign Up for Free</a>
                <p class="mt-3 text-muted">No credit card required. Get approved in 24-48 hours.</p>
            </div>
        </div>
        <div class="cta-shape"></div>
        <div class="cta-shape-2"></div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-5 mb-lg-0">
                    <a href="#" class="footer-logo">
                        <span class="text-primary">Clic</span>terra
                    </a>
                    <p class="footer-text">The leading ad monetization platform for publishers of all sizes. Maximize your revenue with premium advertisers and cutting-edge technology.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Company</h4>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Knowledge Base</a></li>
                        <li><a href="#">Case Studies</a></li>
                        <li><a href="#">API Documentation</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">GDPR Compliance</a></li>
                        <li><a href="#">Advertiser Terms</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <h4 class="footer-title">Products</h4>
                    <ul class="footer-links">
                        <li><a href="#">Display Ads</a></li>
                        <li><a href="#">Native Ads</a></li>
                        <li><a href="#">Video Ads</a></li>
                        <li><a href="#">Referral Program</a></li>
                        <li><a href="#">Advertiser Platform</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <p class="footer-bottom-text mb-0">&copy; <?php echo $current_year; ?> Clicterra. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="footer-bottom-text">
                            Made with <i class="bi bi-heart-fill text-danger"></i> for publishers worldwide.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Floating CTA -->
    <a href="publisher/signup.php" class="floating-cta" id="floatingCta">
        <i class="bi bi-pencil-square"></i> Sign Up Now
    </a>

    <!-- Back to Top -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="bi bi-arrow-up"></i>
    </a>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    
    <!-- Custom JS -->
       <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll behavior
            const navbar = document.getElementById('mainNav');
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
            
            // Initialize testimonials slider
            const testimonialSwiper = new Swiper('.testimonial-swiper', {
                slidesPerView: 1,
                spaceBetween: 30,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true
                },
                breakpoints: {
                    640: {
                        slidesPerView: 1,
                    },
                    768: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    }
                },
                autoplay: {
                    delay: 5000,
                },
            });
            
            // Fade in elements on scroll
            const fadeElements = document.querySelectorAll('.fade-up');
            
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.1
            };
            
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('appear');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            fadeElements.forEach(element => {
                observer.observe(element);
            });
            
            // Back to top button
            const backToTop = document.getElementById('backToTop');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 500) {
                    backToTop.classList.add('show');
                } else {
                    backToTop.classList.remove('show');
                }
            });
            
            backToTop.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Floating CTA
            const floatingCta = document.getElementById('floatingCta');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 1000) {
                    floatingCta.classList.add('show');
                } else {
                    floatingCta.classList.remove('show');
                }
            });
            
            // Current date display
            document.querySelectorAll('.current-date').forEach(element => {
                element.textContent = '2025-07-23'; // Using provided date
            });
            
            // Current user display if logged in
            <?php if(is_logged_in()): ?>
            document.querySelectorAll('.current-user').forEach(element => {
                element.textContent = 'simoncode12lanjutkan'; // Using provided username
            });
            <?php endif; ?>
            
            // Add current time to footer
            const footerTimestamp = document.createElement('div');
            footerTimestamp.classList.add('footer-bottom-text', 'mt-2');
            footerTimestamp.innerHTML = 'Last updated: 2025-07-23 11:57:31 UTC';
            document.querySelector('.footer-bottom .col-md-6:last-child').appendChild(footerTimestamp);
        });
    </script>