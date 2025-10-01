<?php require_once __DIR__ . '/db.php'; ?>
<?php
// Handle contact form submission
$contactSuccess = '';
$contactError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
  $fullName = trim($_POST['fullName'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $message = trim($_POST['message'] ?? '');
  if ($fullName === '' || $email === '' || $message === '') {
    $contactError = 'Please fill in Full Name, Email, and Message.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $contactError = 'Please enter a valid email address.';
  } else {
    $stmt = $mysqli->prepare('INSERT INTO contacts (full_name, email, phone, message) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $fullName, $email, $phone, $message);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
      $contactSuccess = 'Thanks! Your message has been received.';
    } else {
      $contactError = 'Something went wrong. Please try again.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Luner Invest</title>

    <!-- Primary Meta Tags -->
<meta name="title" content="Invest">
<meta name="description" content="invest">

 
    <!--====== Favicon Icon ======-->
    <link
      rel="shortcut icon"
      href="assets/images/favicon.svg"
      type="image/svg"
    />

    <!-- ===== All CSS files ===== -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/animate.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" />
    <link rel="stylesheet" href="assets/css/ud-styles.css" />
  </head>
  <body>
    <!-- ====== Header Start ====== -->
    <header class="ud-header">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <nav class="navbar navbar-expand-lg">
              <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/logo.svg" alt="Logo" /> 
                <h1 class="text-primary"></h1>
              </a>
              <button class="navbar-toggler">
                <span class="toggler-icon"> </span>
                <span class="toggler-icon"> </span>
                <span class="toggler-icon"> </span>
              </button>

              <div class="navbar-collapse">
                <ul id="nav" class="navbar-nav mx-auto">
                  <li class="nav-item">
                    <a class="ud-menu-scroll" href="#home">Home</a>
                  </li>

                  <li class="nav-item">
                    <a class="ud-menu-scroll" href="#about">About</a>
                  </li>
                  <li class="nav-item">
                    <a class="ud-menu-scroll" href="#pricing">Pricing</a>
                  </li>
                  <li class="nav-item">
                    <a class="ud-menu-scroll" href="#team">Team</a>
                  </li>
                  <li class="nav-item">
                    <a class="ud-menu-scroll" href="#contact">Contact</a>
                  </li>
           
                </ul>
              </div>

              <div class="navbar-btn d-none d-sm-inline-block">
                <a href="login.php" class="ud-main-btn ud-login-btn">
                  Sign In
                </a>
                <a class="ud-main-btn ud-white-btn" href="register.php">
                  Sign Up
                </a>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </header>
    <!-- ====== Header End ====== -->
    
    <section class="ud-hero" id="home" style="position: relative; height: 100vh; overflow: hidden;">
  <!-- ✅ Background Video -->
  <video autoplay muted loop playsinline style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2;">
    <source src="assets/videos/investment-bg.mp4" type="video/mp4" />
    Your browser does not support the video tag.
  </video>

  <!-- ✅ Dark Overlay (for better text visibility) -->
  <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: -1;"></div>

  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="ud-hero-content wow fadeInUp" data-wow-delay=".2s">
          <h1 class="ud-hero-title">
            Smart Investing to Achieve Your Life Goals
          </h1>
          <p class="ud-hero-desc">
            Pakistan’s first licensed digital wealth manager offering smart investing and personalized advice.
          </p>
          <ul class="ud-hero-buttons">
            <li>
              <a href="register.php" rel="nofollow noopener" class="ud-main-btn ud-white-btn">
                Register Now
              </a>
            </li>
            <li>
              <a href="#" rel="nofollow noopener" class="ud-main-btn ud-link-btn">
                Learn More <i class="lni lni-arrow-right"></i>
              </a>
            </li>
          </ul>
        </div>

        <div class="ud-hero-image wow fadeInUp" data-wow-delay=".25s">
          <!-- <img src="assets/images/hero/hero-image.svg" alt="hero-image" /> -->
          <img
            src="assets/images/hero/dotted-shape.svg"
            alt="shape"
            class="shape shape-1"
          />
          <img
            src="assets/images/hero/dotted-shape.svg"
            alt="shape"
            class="shape shape-2"
          />
        </div>
      </div>
    </div>
  </div>
</section>


    <!-- ====== Hero Start ====== -->
    <!--<section class="ud-hero" id="home" style="background-image: url('assets/images/banner/main-invest1.svg'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 100vh;">-->
    <!--  <div class="container">-->
    <!--    <div class="row">-->
    <!--      <div class="col-lg-12">-->
    <!--        <div class="ud-hero-content wow fadeInUp" data-wow-delay=".2s">-->
    <!--          <h1 class="ud-hero-title">-->
    <!--            Smart Investing to Achieve Your Life Goals-->
    <!--          </h1>-->
    <!--          <p class="ud-hero-desc">-->
    <!--           Pakistan’s first licensed digital wealth manager offering smart investing and personalized advice .-->
    <!--          </p>-->
    <!--          <ul class="ud-hero-buttons">-->
    <!--            <li>-->
    <!--              <a href="register.php" rel="nofollow noopener" class="ud-main-btn ud-white-btn">-->
    <!--                Register Now-->
    <!--              </a>-->
    <!--            </li>-->
    <!--            <li>-->
    <!--              <a href="#" rel="nofollow noopener"  class="ud-main-btn ud-link-btn">-->
    <!--                Learn More <i class="lni lni-arrow-right"></i>-->
    <!--              </a>-->
    <!--            </li>-->
    <!--          </ul>-->
    <!--        </div>-->

    <!--        <div class="ud-hero-image wow fadeInUp" data-wow-delay=".25s">-->
              <!-- <img src="assets/images/hero/hero-image.svg" alt="hero-image" /> -->
    <!--          <img-->
    <!--            src="assets/images/hero/dotted-shape.svg"-->
    <!--            alt="shape"-->
    <!--            class="shape shape-1"-->
    <!--          />-->
    <!--          <img-->
    <!--            src="assets/images/hero/dotted-shape.svg"-->
    <!--            alt="shape"-->
    <!--            class="shape shape-2"-->
    <!--          />-->
    <!--        </div>-->
    <!--      </div>-->
    <!--    </div>-->
    <!--  </div>-->
    <!--</section>-->
    <!-- ====== Hero End ====== -->

    <!-- ====== Features Start ====== -->
    <section id="features" class="ud-features">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-section-title">
              <span>Luner</span>
              <h2>Fixed Income Investments
</h2>
              <p>
              Grow your wealth securely with Luner Trades through low-risk bonds and fixed income portfolios managed by top-tier experts.
              </p>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-feature wow fadeInUp" data-wow-delay=".1s">
              <div class="ud-feature-icon">
                <i class="lni lni-gift"></i>
              </div>
              <div class="ud-feature-content">
                <h3 class="ud-feature-title">Equity Growth Plans</h3>
                <p class="ud-feature-desc">
                  Build long-term wealth with diversified equity investments designed to deliver consistent growth.
                </p>
                <a href="javascript:void(0)" class="ud-feature-link">
                  Learn More
                </a>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-feature wow fadeInUp" data-wow-delay=".15s">
              <div class="ud-feature-icon">
                <i class="lni lni-move"></i>
              </div>
              <div class="ud-feature-content">
                <h3 class="ud-feature-title">Alternative Assets</h3>
                <p class="ud-feature-desc">
                 Access real estate, private credit, and commodities to balance risk and enhance returns.
                </p>
                <a href="javascript:void(0)" class="ud-feature-link">
                  Learn More
                </a>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-feature wow fadeInUp" data-wow-delay=".2s">
              <div class="ud-feature-icon">
                <i class="lni lni-layout"></i>
              </div>
              <div class="ud-feature-content">
                <h3 class="ud-feature-title">Sustainable Investing</h3>
                <p class="ud-feature-desc">
                Invest with purpose through ESG-focused portfolios that create both profit and positive impact.
                </p>
                <a href="javascript:void(0)" class="ud-feature-link">
                  Learn More
                </a>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-feature wow fadeInUp" data-wow-delay=".25s">
              <div class="ud-feature-icon">
                <i class="lni lni-layers"></i>
              </div>
              <div class="ud-feature-content">
                <h3 class="ud-feature-title">Why Luner Trades</h3>
                <p class="ud-feature-desc">
                  Trusted, regulated, and client-focused with transparent fees and expert strategies.
                </p>
                <a href="javascript:void(0)" class="ud-feature-link">
                  Learn More
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Features End ====== -->

    <!-- ====== About Start ====== -->
    <section id="about" class="ud-about">
      <div class="container">
        <div class="ud-about-wrapper wow fadeInUp" data-wow-delay=".2s">
          <div class="ud-about-content-wrapper">
            <div class="ud-about-content">
              <span class="tag">About Us</span>
              <h2>Luner Smart Platform to Grow Your Money Safely.</h2>
              <p>
               Our main focus is to help people learn how to grow their savings
               with safe and high-return investment plans. We guide you
               step by step with the help of experienced experts.
              </p>

              <p>
                The main goal is to help individuals protect and grow their capital
                with strategic investment plans, expert consultation, and
                transparent portfolio management.
              </p>
              <a href="javascript:void(0)" class="ud-main-btn">Learn More</a>
            </div>
          </div>
          <div class="ud-about-image">
            <img src="assets/images/about/about-image.svg" alt="about-image" />
          </div>
        </div>
      </div>
    </section>
    <!-- ====== About End ====== -->

    <!-- ====== Pricing Start ====== -->
    <section id="pricing" class="ud-pricing">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-section-title mx-auto text-center">
              <span>Pricing</span>
              <h2>Our Investment Plans</h2>
              <p>
               Luner offer investment options to match your budget and goals.
              </p>
            </div>
          </div>
        </div>

        <div class="row g-0 align-items-center justify-content-center">
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing first-item wow fadeInUp"
              data-wow-delay=".15s"
            >
              <div class="ud-pricing-header">
                <h3>STARTER PLAN</h3>
                <h4>STARTING FROM<br>
PKR 5,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission 10%</li>
                  <li>Access to basic profit plans</li>
                  <li>Safe monthly investment</li>
                  <li>Free updates</li>
                  <li>Withdraw anytime</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing active wow fadeInUp"
              data-wow-delay=".1s"
            >
              <span class="ud-popular-tag">POPULAR</span>
              <div class="ud-pricing-header">
                <h3>GROWTH PLAN</h3>
                <h4>STARTING FROM<br>
PKR 10,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission 10%</li>
                  <li>Higher monthly returns</li>
                  <li>Withdraw anytime</li>
                  <li>Expert guidance included</li>
                  <li>Higher profit potential</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-white-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing last-item wow fadeInUp"
              data-wow-delay=".15s"
            >
              <div class="ud-pricing-header">
                <h3>ADVANCED PLAN</h3>
                <h4>STARTING FROM<br>
PKR 20,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission up to 10%</li>
                  <li>Higher profit potential</li>
                  <li>Full investment dashboard</li>
                  <li>Priority support</li>
                  <li>Withdraw anytime</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
        </div>
        
     <!-- ====== 2ND Pricing CARD ====== -->
        <div class="row g-0 align-items-center justify-content-center">
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing first-item wow fadeInUp"
              data-wow-delay=".15s"
            >
              <div class="ud-pricing-header">
                <h3>STARTER PLAN</h3>
                <h4>STARTING FROM<br>
PKR 5,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission 10%</li>
                  <li>Access to basic profit plans</li>
                  <li>Safe monthly investment</li>
                  <li>Free updates</li>
                  <li>Withdraw anytime</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing active wow fadeInUp"
              data-wow-delay=".1s"
            >
              <span class="ud-popular-tag">POPULAR</span>
              <div class="ud-pricing-header">
                <h3>GROWTH PLAN</h3>
                <h4>STARTING FROM<br>
PKR 10,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission 10%</li>
                  <li>Higher monthly returns</li>
                  <li>Withdraw anytime</li>
                  <li>Expert guidance included</li>
                  <li>Higher profit potential</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-white-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 col-sm-10">
            <div
              class="ud-single-pricing last-item wow fadeInUp"
              data-wow-delay=".15s"
            >
              <div class="ud-pricing-header">
                <h3>ADVANCED PLAN</h3>
                <h4>STARTING FROM<br>
PKR 20,000 /mo</h4>
              </div>
              <div class="ud-pricing-body">
                <ul>
                  <li>Daily commission up to 10%</li>
                  <li>Higher profit potential</li>
                  <li>Full investment dashboard</li>
                  <li>Priority support</li>
                  <li>Withdraw anytime</li>
                  <li>4 Months support</li>
                </ul>
              </div>
              <div class="ud-pricing-footer">
                <a href="javascript:void(0)" class="ud-main-btn ud-border-btn">
                  Purchase Now
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
   
    <!-- ====== Pricing End ====== -->

    <!-- ====== FAQ Start ====== -->
    <section id="faq" class="ud-faq">
      <div class="shape">
        <img src="assets/images/faq/shape.svg" alt="shape" />
      </div>
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-section-title text-center mx-auto">
              <span>FAQ</span>
              <h2>Any Questions? Answered</h2>
              <p>
               We’ve shared some of the most common questions investors ask us.
               If you still have more questions, feel free to contact our support team anytime.
              </p>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-6">
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseOne"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>How can I start investing?</span>
                </button>
                <div id="collapseOne" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
                   Create a free account, choose the plan that suits your budget, deposit the amount, 
                   and your daily earnings will start within 24 hours.
                  </div>
                </div>
              </div>
            </div>
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseTwo"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>How is daily profit calculated?</span>
                </button>
                <div id="collapseTwo" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
                   Your daily profit depends on the plan you choose. For example, if your plan offers 4% daily, 
                   you’ll receive 4% of your invested amount every day.
                  </div>
                </div>
              </div>
            </div>
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseThree"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>Is my money safe?</span>
                </button>
                <div id="collapseThree" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
                  Yes, we use secure payment systems and invest only in trusted, 
                  low-risk markets to keep your money safe and growing.
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".1s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseFour"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>Can I withdraw my profit anytime?</span>
                </button>
                <div id="collapseFour" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
                   Yes, you can withdraw your daily profit anytime through your account dashboard.
                   Payments are usually processed within 24 hours.
                  </div>
                </div>
              </div>
            </div>
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".15s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseFive"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>Do you help new investors?</span>
                </button>
                <div id="collapseFive" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
                   Yes, our expert team is always available to guide new investors and explain how the plans and profits work.
                  </div>
                </div>
              </div>
            </div>
            <div class="ud-single-faq wow fadeInUp" data-wow-delay=".2s">
              <div class="accordion">
                <button
                  class="ud-faq-btn collapsed"
                  data-bs-toggle="collapse"
                  data-bs-target="#collapseSix"
                >
                  <span class="icon flex-shrink-0">
                    <i class="lni lni-chevron-down"></i>
                  </span>
                  <span>Where is my money invested?</span>
                </button>
                <div id="collapseSix" class="accordion-collapse collapse">
                  <div class="ud-faq-body">
We invest in safe, regulated sectors in Pakistan and manage everything for you — so you just invest and earn without worry.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== FAQ End ====== -->

    <!-- ====== Testimonials Start ====== -->
    <section id="testimonials" class="ud-testimonials">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-section-title mx-auto text-center">
              <span>Testimonials</span>
              <h2>What Luner Trades Investors Say</h2>
              <p>
                Here’s what our happy clients think about investing with us. 
                Their success stories show how easy and safe it is to grow your money with our trusted plans.
              </p>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-4 col-md-6">
            <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".1s"
            >
              <div class="ud-testimonial-ratings">
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
              </div>
              <div class="ud-testimonial-content">
                <p>
                  “I started with a small plan and now earn daily profit without any stress. 
                  The process is easy, and I can withdraw anytime. Highly recommended!
                </p>
              </div>
              <div class="ud-testimonial-info">
                <div class="ud-testimonial-image">
                  <img
                    src="assets/images/testimonials/author-01.png"
                    alt="author"
                  />
                </div>
                <div class="ud-testimonial-meta">
                  <h4>Shoaib Ahmed</h4>
                  <p>Investor @Karachi</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".15s"
            >
              <div class="ud-testimonial-ratings">
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
              </div>
              <div class="ud-testimonial-content">
                <p>
                  “This platform made investing simple for me. I receive daily commissions and full support whenever I need it.
                  It’s a safe and trusted way to grow money.
                </p>
              </div>
              <div class="ud-testimonial-info">
                <div class="ud-testimonial-image">
                  <img
                    src="assets/images/testimonials/author-02.png"
                    alt="author"
                  />
                </div>
                <div class="ud-testimonial-meta">
                  <h4>Maria Khan</h4>
                  <p>Investor @Lahore</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6">
            <div
              class="ud-single-testimonial wow fadeInUp"
              data-wow-delay=".2s"
            >
              <div class="ud-testimonial-ratings">
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
                <i class="lni lni-star-filled"></i>
              </div>
              <div class="ud-testimonial-content">
                <p>
                  “I was new to investing, but their team guided me at every step. 
                  Now I’m earning steady returns every day. Great experience so far!
                </p>
              </div>
              <div class="ud-testimonial-info">
                <div class="ud-testimonial-image">
                  <img
                    src="assets/images/testimonials/author-03.png"
                    alt="author"
                  />
                </div>
                <div class="ud-testimonial-meta">
                  <h4>Hassan Raza</h4>
                  <p>Investor @Islamabad</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-brands wow fadeInUp" data-wow-delay=".2s">
              <div class="ud-title">
                <h6>Trusted and Used by</h6>
              </div>
              <div class="ud-brands-logo">
                <div class="ud-single-logo">
                  <img src="assets/images/brands/ayroui.svg" alt="ayroui" />
                </div>
                <div class="ud-single-logo">
                  <img src="assets/images/brands/uideck.svg" alt="uideck" />
                </div>
                <div class="ud-single-logo">
                  <img
                    src="assets/images/brands/graygrids.svg"
                    alt="graygrids"
                  />
                </div>
                <div class="ud-single-logo">
                  <img
                    src="assets/images/brands/lineicons.svg"
                    alt="lineicons"
                  />
                </div>
                <div class="ud-single-logo">
                  <img
                    src="assets/images/brands/ecommerce-html.svg"
                    alt="ecommerce-html"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Testimonials End ====== -->

    <!-- ====== Team Start ====== -->
    <section id="team" class="ud-team">
      <div class="container">
        <div class="row">
          <div class="col-lg-12">
            <div class="ud-section-title mx-auto text-center">
              <span>Our Team</span>
              <h2>Meet The Experts</h2>
              <p>
                There are many variations of passages of Lorem Ipsum available
                but the majority have suffered alteration in some form.
              </p>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-team wow fadeInUp" data-wow-delay=".1s">
              <div class="ud-team-image-wrapper">
                <div class="ud-team-image">
                  <img src="assets/images/team/team-01.png" alt="team" />
                </div>

                <img
                  src="assets/images/team/dotted-shape.svg"
                  alt="shape"
                  class="shape shape-1"
                />
                <img
                  src="assets/images/team/shape-2.svg"
                  alt="shape"
                  class="shape shape-2"
                />
              </div>
              <div class="ud-team-info">
                <h5>Adveen Desuza</h5>
                <h6>UI Designer</h6>
              </div>
              <ul class="ud-team-socials">
                <li>
                  <a href="#">
                    <i class="lni lni-facebook-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-twitter-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-instagram-filled"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-team wow fadeInUp" data-wow-delay=".15s">
              <div class="ud-team-image-wrapper">
                <div class="ud-team-image">
                  <img src="assets/images/team/team-02.png" alt="team" />
                </div>

                <img
                  src="assets/images/team/dotted-shape.svg"
                  alt="shape"
                  class="shape shape-1"
                />
                <img
                  src="assets/images/team/shape-2.svg"
                  alt="shape"
                  class="shape shape-2"
                />
              </div>
              <div class="ud-team-info">
                <h5>Jezmin uniya</h5>
                <h6>Product Designer</h6>
              </div>
              <ul class="ud-team-socials">
                <li>
                  <a href="#">
                    <i class="lni lni-facebook-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-twitter-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-instagram-filled"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-team wow fadeInUp" data-wow-delay=".2s">
              <div class="ud-team-image-wrapper">
                <div class="ud-team-image">
                  <img src="assets/images/team/team-03.png" alt="team" />
                </div>

                <img
                  src="assets/images/team/dotted-shape.svg"
                  alt="shape"
                  class="shape shape-1"
                />
                <img
                  src="assets/images/team/shape-2.svg"
                  alt="shape"
                  class="shape shape-2"
                />
              </div>
              <div class="ud-team-info">
                <h5>Andrieo Gloree</h5>
                <h6>App Developer</h6>
              </div>
              <ul class="ud-team-socials">
                <li>
                  <a href="#">
                    <i class="lni lni-facebook-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-twitter-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-instagram-filled"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div class="col-xl-3 col-lg-3 col-sm-6">
            <div class="ud-single-team wow fadeInUp" data-wow-delay=".25s">
              <div class="ud-team-image-wrapper">
                <div class="ud-team-image">
                  <img src="assets/images/team/team-04.png" alt="team" />
                </div>

                <img
                  src="assets/images/team/dotted-shape.svg"
                  alt="shape"
                  class="shape shape-1"
                />
                <img
                  src="assets/images/team/shape-2.svg"
                  alt="shape"
                  class="shape shape-2"
                />
              </div>
              <div class="ud-team-info">
                <h5>Jackie Sanders</h5>
                <h6>Content Writer</h6>
              </div>
              <ul class="ud-team-socials">
                <li>
                  <a href="#">
                    <i class="lni lni-facebook-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-twitter-filled"></i>
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="lni lni-instagram-filled"></i>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Team End ====== -->

    <!-- ====== Contact Start ====== -->
    <section id="contact" class="ud-contact">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-xl-8 col-lg-7">
            <div class="ud-contact-content-wrapper">
              <div class="ud-contact-title">
                <span>CONTACT US</span>
                <h2>
                  Let’s talk about <br />
                  Love to hear from you!
                </h2>
              </div>
              <div class="ud-contact-info-wrapper">
                <div class="ud-single-info">
                  <div class="ud-info-icon">
                    <i class="lni lni-map-marker"></i>
                  </div>
                  <div class="ud-info-meta">
                    <h5>Our Location</h5>
                    <p>401 Broadway, 24th Floor, Orchard Cloud View, London</p>
                  </div>
                </div>
                <div class="ud-single-info">
                  <div class="ud-info-icon">
                    <i class="lni lni-envelope"></i>
                  </div>
                  <div class="ud-info-meta">
                    <h5>How Can We Help?</h5>
                    <p>info@luner.com</p>
                    <p>contact@luner.com</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-4 col-lg-5">
            <div
              class="ud-contact-form-wrapper wow fadeInUp"
              data-wow-delay=".2s"
            >
              <h3 class="ud-contact-form-title">Send us a Message</h3>
              <?php if (!empty($contactSuccess)): ?><div class="alert alert-success" role="alert"><?php echo htmlspecialchars($contactSuccess); ?></div><?php endif; ?>
              <?php if (!empty($contactError)): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($contactError); ?></div><?php endif; ?>
              <form class="ud-contact-form" method="post" action="#contact">
                <input type="hidden" name="contact_submit" value="1" />
                <div class="ud-form-group">
                  <label for="fullName">Full Name*</label>
                  <input
                    type="text"
                    name="fullName"
                    placeholder="Adam Gelius"
                    value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>"
                  />
                </div>
                <div class="ud-form-group">
                  <label for="email">Email*</label>
                  <input
                    type="email"
                    name="email"
                    placeholder="example@yourmail.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                  />
                </div>
                <div class="ud-form-group">
                  <label for="phone">Phone*</label>
                  <input
                    type="text"
                    name="phone"
                    placeholder="+885 1254 5211 552"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                  />
                </div>
                <div class="ud-form-group">
                  <label for="message">Message*</label>
                  <textarea
                    name="message"
                    rows="1"
                    placeholder="type your message here"
                  ><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                <div class="ud-form-group mb-0">
                  <button type="submit" class="ud-main-btn">
                    Send Message
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- ====== Contact End ====== -->

    <!-- ====== Footer Start ====== -->
    <footer class="ud-footer wow fadeInUp" data-wow-delay=".15s">
      <div class="shape shape-1">
        <img src="assets/images/footer/shape-1.svg" alt="shape" />
      </div>
      <div class="shape shape-2">
        <img src="assets/images/footer/shape-2.svg" alt="shape" />
      </div>
      <div class="shape shape-3">
        <img src="assets/images/footer/shape-3.svg" alt="shape" />
      </div>
      <div class="ud-footer-widgets">
        <div class="container">
          <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-6">
              <div class="ud-widget">
                <a href="index.html" class="ud-footer-logo">
                  <img src="assets/images/logo/logo.svg" alt="logo" />
                </a>
                <p class="ud-widget-desc">
                 We create smart investment opportunities for people and businesses by using secure financial strategies.
                </p>
                <ul class="ud-widget-socials">
                  <li>
                    <a href="#">
                      <i class="lni lni-facebook-filled"></i>
                    </a>
                  </li>
                  <li>
                    <a href="#">
                      <i class="lni lni-twitter-filled"></i>
                    </a>
                  </li>
                  <li>
                    <a href="#">
                      <i class="lni lni-instagram-filled"></i>
                    </a>
                  </li>
                  <li>
                    <a href="#">
                      <i class="lni lni-linkedin-original"></i>
                    </a>
                  </li>
                </ul>
              </div>
            </div>

            <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
              <div class="ud-widget">
                <h5 class="ud-widget-title">About Us</h5>
                <ul class="ud-widget-links">
                  <li>
                    <a href="javascript:void(0)">Home</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">Features</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">About</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">Testimonial</a>
                  </li>
                </ul>
              </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6">
              <div class="ud-widget">
                <h5 class="ud-widget-title">Features</h5>
                <ul class="ud-widget-links">
                  <li>
                    <a href="javascript:void(0)">How it works</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">Privacy policy</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">Terms of service</a>
                  </li>
                  <li>
                    <a href="javascript:void(0)">Refund policy</a>
                  </li>
                </ul>
              </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-6 col-sm-6">
              <div class="ud-widget">
                <h5 class="ud-widget-title">Our Products</h5>
                <ul class="ud-widget-links">
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                      >Lineicons
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                      >Ecommerce HTML</a
                    >
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                      >Ayro UI</a
                    >
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                      >Plain Admin</a
                    >
                  </li>
                </ul>
              </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-8 col-sm-10">
              <div class="ud-widget">
                <h5 class="ud-widget-title">Partners</h5>
                <ul class="ud-widget-brands">
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/ayroui.svg"
                        alt="ayroui"
                      />
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/ecommerce-html.svg"
                        alt="ecommerce-html"
                      />
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/graygrids.svg"
                        alt="graygrids"
                      />
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/lineicons.svg"
                        alt="lineicons"
                      />
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/uideck.svg"
                        alt="uideck"
                      />
                    </a>
                  </li>
                  <li>
                    <a
                      href="#"
                      rel="nofollow noopner"
                      target="_blank"
                    >
                      <img
                        src="assets/images/footer/brands/tailwindtemplates.svg"
                        alt="tailwindtemplates"
                      />
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="ud-footer-bottom">
        <div class="container">
          <div class="row">
            <div class="col-md-8">
              <ul class="ud-footer-bottom-left">
                <li>
                  <a href="javascript:void(0)">Privacy policy</a>
                </li>
                <li>
                  <a href="javascript:void(0)">Support policy</a>
                </li>
                <li>
                  <a href="javascript:void(0)">Terms of service</a>
                </li>
              </ul>
            </div>
            <div class="col-md-4">
              <p class="ud-footer-bottom-right">
                Designed and Developed by
                <a href="https://alifcode.com/" rel="nofollow">Alifcode Technologies</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <!-- ====== Footer End ====== -->

    <!-- ====== Back To Top Start ====== -->
    <a href="javascript:void(0)" class="back-to-top">
      <i class="lni lni-chevron-up"> </i>
    </a>
    <!-- ====== Back To Top End ====== -->

    <!-- ====== All Javascript Files ====== -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
      // ==== for menu scroll
      const pageLink = document.querySelectorAll(".ud-menu-scroll");

      pageLink.forEach((elem) => {
        elem.addEventListener("click", (e) => {
          e.preventDefault();
          document.querySelector(elem.getAttribute("href")).scrollIntoView({
            behavior: "smooth",
            offsetTop: 1 - 60,
          });
        });
      });

      // section menu active
      function onScroll(event) {
        const sections = document.querySelectorAll(".ud-menu-scroll");
        const scrollPos =
          window.pageYOffset ||
          document.documentElement.scrollTop ||
          document.body.scrollTop;

        for (let i = 0; i < sections.length; i++) {
          const currLink = sections[i];
          const val = currLink.getAttribute("href");
          const refElement = document.querySelector(val);
          const scrollTopMinus = scrollPos + 73;
          if (
            refElement.offsetTop <= scrollTopMinus &&
            refElement.offsetTop + refElement.offsetHeight > scrollTopMinus
          ) {
            document
              .querySelector(".ud-menu-scroll")
              .classList.remove("active");
            currLink.classList.add("active");
          } else {
            currLink.classList.remove("active");
          }
        }
      }

      window.document.addEventListener("scroll", onScroll);
    </script>
  </body>
</html>
