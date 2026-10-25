<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fname'])) {
    $isAjax  = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $fname   = trim($_POST['fname'] ?? '');
    $lname   = trim($_POST['lname'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($fname) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($phone)) {
        $to      = 'docprasanna@yahoo.com';
        $subject = 'New Inquiry from ' . $fname . ' ' . $lname;
        $body    = "Name: $fname $lname\nEmail: $email\nPhone: $phone\nService: $service\n\nMessage:\n$message";
        $headers = "From: noreply@drprasannakumar.com\r\nReply-To: $email\r\n";
        @mail($to, $subject, $body, $headers);
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); exit; }
        header('Location: index.php?sent=1#contact');
    } else {
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok' => false]); exit; }
        header('Location: index.php?err=1#contact');
    }
    exit;
}
$sent = isset($_GET['sent']);
$err  = isset($_GET['err']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dr. Prasanna Kumar K</title>
  <meta name="description" content="Online consultations with Dr. Prasanna Kumar K — expert primary care, diabetes, hypertension, occupational health and chronic disease management from anywhere." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

<!-- ===== HEADER ===== -->
<header>
  <div class="container">
    <nav class="navbar">
      <div class="logo">
        <div class="logo-icon"><i class="fas fa-stethoscope"></i></div>
        <div class="logo-text">
          <h1>Dr. Prasanna Kumar K</h1>
        </div>
      </div>

      <ul class="nav-links">
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#doctors">Doctors</a></li>
        <li><a href="#blog">Industrial Health</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="patient-portal.php" style="color:var(--gold-light,#f0a93a);font-weight:600;"><i class="fas fa-folder-open"></i> My Records</a></li>
      </ul>

      <div class="nav-cta">
        <a href="tel:+919538099960" class="call-btn">
          <i class="fas fa-phone"></i> Call Now
        </a>
        <div class="hamburger">
          <span></span><span></span><span></span>
        </div>
      </div>
    </nav>
  </div>
</header>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
  <div class="container">
    <div class="hero-content">
      <h2>Get Expert<br /><span>Comprehensive Medical Care</span></h2>
      <p>
        Expert primary care and comprehensive medical care — available online.
        Trusted diagnosis, chronic disease management, and compassionate care
        from the comfort of your home.
      </p>
      <div class="hero-btns">
        <a href="appointment.php" class="btn-primary">
          <i class="fas fa-calendar-check"></i>&nbsp; Book an Appointment
        </a>
        <a href="https://wa.me/919538099960" class="whatsapp-btn" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp"></i> WhatsApp Now
        </a>
      </div>
    </div>
  </div>
</section>


<!-- ===== ABOUT ===== -->
<section class="about section-pad" id="about">
  <div class="container">
    <div class="about-grid">
      <!-- Image -->
      <div class="about-image-wrap fade-in">
        <img
          src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=800&q=80"
          alt="Clinic interior"
        />
        <div class="about-badge-float">
          <div class="num">15+</div>
          <div class="lbl">Years Experience</div>
        </div>
      </div>

      <!-- Content -->
      <div class="fade-in">
        <p class="gradient-text" style="font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;">About Us</p>
        <h2 class="section-title" style="margin-top:8px;">
          Dr. Prasanna Kumar K
        </h2>
        <div class="divider-line"></div>
        <p style="color:#555;margin-bottom:24px;">
          We are a premier comprehensive medical care clinic in Bengaluru, equipped with
          state-of-the-art technology and guided by experienced specialists. Our practice
          offers comprehensive primary and preventive care — from diagnosis to treatment for acute and chronic medical conditions.
        </p>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="num" data-target="10000" data-suffix="+">0</div>
            <div class="lbl">Patients Treated</div>
          </div>
          <div class="stat-card">
            <div class="num" data-target="98" data-suffix="%">0</div>
            <div class="lbl">Satisfaction Rate</div>
          </div>
          <div class="stat-card">
            <div class="num" data-target="9" data-suffix="+">0</div>
            <div class="lbl">Services Offered</div>
          </div>
          <div class="stat-card">
            <div class="num" data-target="15" data-suffix="+">0</div>
            <div class="lbl">Years of Experience</div>
          </div>
        </div>

        <div class="about-features">
          <div class="feature-item">
            <div class="icon"><i class="fas fa-check"></i></div>
            <span>Comprehensive primary and preventive care for all age groups</span>
          </div>
          <div class="feature-item">
            <div class="icon"><i class="fas fa-check"></i></div>
            <span>Expert management of chronic conditions including diabetes and hypertension</span>
          </div>
          <div class="feature-item">
            <div class="icon"><i class="fas fa-check"></i></div>
            <span>In-house diagnostic services and rapid test results</span>
          </div>
          <div class="feature-item">
            <div class="icon"><i class="fas fa-check"></i></div>
            <span>Personalised treatment plans with follow-up care</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== SERVICES ===== -->
<section class="services section-pad" id="services">
  <div class="container">
    <div class="services-header fade-in">
      <p class="gradient-text" style="font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;">What We Offer</p>
      <h2 class="section-title">Our Specialised Services</h2>
      <div class="divider-line"></div>
      <p class="section-subtitle">
        Comprehensive comprehensive medical care services powered by evidence-based practice and personalised care.
      </p>
    </div>

    <div class="services-grid">
      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=600&q=80" alt="Primary Care & Preventive Medicine" />
        </div>
        <div class="card-body">
          <h3>Primary Care &amp; Preventive Medicine</h3>
          <p>Comprehensive health check-ups, screenings, and preventive care for individuals and families.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1559757175-5700dde675bc?w=600&q=80" alt="Diabetes & Metabolic Disorders" />
        </div>
        <div class="card-body">
          <h3>Diabetes &amp; Metabolic Disorders</h3>
          <p>Expert management of Type 1, Type 2 diabetes, and metabolic syndrome with personalised treatment plans.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1628348068343-c6a848d2b6dd?w=600&q=80" alt="Hypertension & Cardiovascular Risk" />
        </div>
        <div class="card-body">
          <h3>Hypertension &amp; Cardiovascular Risk</h3>
          <p>Diagnosis and long-term management of high blood pressure and cardiovascular risk factors.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=600&q=80" alt="Respiratory Conditions" />
        </div>
        <div class="card-body">
          <h3>Respiratory Conditions</h3>
          <p>Treatment of asthma, COPD, bronchitis, and other acute and chronic respiratory illnesses.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=600&q=80" alt="Infectious Diseases" />
        </div>
        <div class="card-body">
          <h3>Infectious Diseases</h3>
          <p>Diagnosis and management of common and complex infections including viral, bacterial, and parasitic conditions.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1530026405186-ed1f139313f8?w=600&q=80" alt="Thyroid & Endocrine Disorders" />
        </div>
        <div class="card-body">
          <h3>Thyroid &amp; Endocrine Disorders</h3>
          <p>Evaluation and management of thyroid diseases, hormonal imbalances, and endocrine conditions.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=600&q=80" alt="Geriatric Care" />
        </div>
        <div class="card-body">
          <h3>Geriatric Care</h3>
          <p>Specialised care for elderly patients including multi-morbidity management and fall prevention.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=600&q=80" alt="Women's Health" />
        </div>
        <div class="card-body">
          <h3>Women's Health</h3>
          <p>General medical care for women including anaemia, PCOS support, and routine health screening.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="service-card">
        <div class="img-wrap">
          <img src="https://images.unsplash.com/photo-1516549655169-df83a0774514?w=600&q=80" alt="Minor Procedures & Wound Care" />
        </div>
        <div class="card-body">
          <h3>Minor Procedures &amp; Wound Care</h3>
          <p>In-clinic procedures including wound dressing, injections, and minor surgical care.</p>
          <a href="#contact" class="learn-more">Learn More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== DOCTOR ===== -->
<section class="doctor-section section-pad" id="doctors">
  <div class="container">
    <div class="doctor-section-header fade-in">
      <p class="label-tag">Meet Our Doctor</p>
      <h2 class="section-title">Our Lead Specialist</h2>
      <div class="divider-line"></div>
    </div>

    <div class="doctor-profile">
      <div class="doctor-profile-img">
        <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=700&q=80" alt="Dr. Prasanna" />
      </div>
      <div class="doctor-profile-info">
        <p class="label-tag" style="margin-bottom:10px;">Occupational Health Physician &amp; Primary Care Specialist</p>
        <h3 class="dp-name">Dr. Prasanna Kumar K</h3>
        <p class="dp-credentials">MBBS, AFIH</p>

        <div class="dp-divider"></div>

        <p class="dp-bio">
          Dr. Prasanna Kumar K is a qualified Occupational Health Physician holding MBBS and AFIH
          (Associate Fellowship in Industrial Health). With 15+ years of clinical experience, he specialises
          in industrial and occupational medicine, primary care, and chronic disease management. He is
          committed to delivering evidence-based, patient-centred care with a compassionate approach to
          every patient — in both clinical and workplace settings.
        </p>

        <div class="dp-highlights">
          <div class="dp-highlight-item">
            <i class="fas fa-graduation-cap"></i>
            <span>AFIH – Associate Fellowship in Industrial Health</span>
          </div>
          <div class="dp-highlight-item">
            <i class="fas fa-hard-hat"></i>
            <span>Occupational &amp; Industrial Medicine Expert</span>
          </div>
          <div class="dp-highlight-item">
            <i class="fas fa-book-medical"></i>
            <span>Published Research in Medical Journals</span>
          </div>
          <div class="dp-highlight-item">
            <i class="fas fa-user-md"></i>
            <span>15+ Years of Clinical Experience</span>
          </div>
        </div>

        <div class="dp-tags">
          <span class="tag">Industrial Health</span>
          <span class="tag">Occupational Medicine</span>
          <span class="tag">AFIH</span>
          <span class="tag">Primary Care</span>
          <span class="tag">Preventive Medicine</span>
          <span class="tag">Chronic Disease Management</span>
        </div>

        <a href="appointment.php" class="btn-primary" style="margin-top:28px;">
          <i class="fas fa-calendar-check"></i> Book an Appointment
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ===== BLOG ===== -->
<section class="blog section-pad" id="blog">
  <div class="container">
    <div class="blog-header fade-in">
      <p class="gradient-text" style="font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;">Workplace Wellness</p>
      <h2 class="section-title">Industrial Health</h2>
      <div class="divider-line"></div>
      <p class="section-subtitle">Expert insights on occupational health, workplace safety, and industrial medicine from Dr. Prasanna Kumar K.</p>
    </div>

    <div class="blog-grid">
      <div class="blog-card">
        <div class="blog-img">
          <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=600&q=80" alt="Industrial Health 1" />
        </div>
        <div class="blog-body">
          <span class="blog-cat">Occupational Safety</span>
          <h3>Managing Workplace Stress: A Guide for Employees and Employers</h3>
          <p>Work-related stress is one of the leading causes of occupational illness. Learn how to identify, manage, and prevent burnout and mental health risks in industrial settings.</p>
          <div class="blog-meta">
            <span><i class="fas fa-user"></i> Dr. Prasanna Kumar K</span>
            <span><i class="fas fa-calendar"></i> March 2025</span>
          </div>
          <a href="#blog" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="blog-card">
        <div class="blog-img">
          <img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=600&q=80" alt="Industrial Health 2" />
        </div>
        <div class="blog-body">
          <span class="blog-cat">Industrial Medicine</span>
          <h3>Common Occupational Diseases and How to Prevent Them</h3>
          <p>From respiratory conditions caused by dust and fumes to musculoskeletal disorders from repetitive strain — learn about the most prevalent workplace health risks and prevention strategies.</p>
          <div class="blog-meta">
            <span><i class="fas fa-user"></i> Dr. Prasanna Kumar K</span>
            <span><i class="fas fa-calendar"></i> February 2025</span>
          </div>
          <a href="#blog" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <div class="blog-card">
        <div class="blog-img">
          <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&q=80" alt="Industrial Health 3" />
        </div>
        <div class="blog-body">
          <span class="blog-cat">Workplace Wellness</span>
          <h3>Pre-Employment Medical Examinations: What to Expect</h3>
          <p>Understanding the purpose and process of pre-employment health assessments — what tests are conducted, why they matter, and how they protect both workers and employers.</p>
          <div class="blog-meta">
            <span><i class="fas fa-user"></i> Dr. Prasanna Kumar K</span>
            <span><i class="fas fa-calendar"></i> January 2025</span>
          </div>
          <a href="#blog" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="contact section-pad" id="contact">
  <div class="container">
    <div class="contact-grid">
      <!-- Info -->
      <div class="fade-in">
        <p class="gradient-text" style="font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;">Get In Touch</p>
        <h2>Online Consultation</h2>
        <div class="divider-line"></div>
        <p>Book an online consultation with Dr. Prasanna Kumar K from the comfort of your home. Available via phone or email.</p>

        <div class="contact-details">
          <div class="contact-item">
            <div class="icon-wrap"><i class="fas fa-video"></i></div>
            <div class="detail">
              <h4>Online Consultation</h4>
              <p>Available via video call &amp; phone</p>
            </div>
          </div>

          <div class="contact-item">
            <div class="icon-wrap"><i class="fas fa-envelope"></i></div>
            <div class="detail">
              <h4>Email</h4>
              <p><a href="mailto:docprasanna@yahoo.com">docprasanna@yahoo.com</a></p>
            </div>
          </div>
        </div>

      </div>

      <!-- Form -->
      <div class="contact-form-wrap fade-in">
        <h3>Quick Inquiry &nbsp;<a href="appointment.php" style="font-size:13px;font-weight:600;background:linear-gradient(135deg,#7ace4c,#017e9d);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Book Online →</a></h3>
        <form id="contactForm" method="post" action="">
          <div class="form-row">
            <div class="form-group">
              <label for="fname">First Name *</label>
              <input type="text" id="fname" name="fname" placeholder="Your first name" required />
            </div>
            <div class="form-group">
              <label for="lname">Last Name</label>
              <input type="text" id="lname" name="lname" placeholder="Your last name" />
            </div>
          </div>
          <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" placeholder="your@email.com" required />
          </div>
          <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input type="tel" id="phone" name="phone" placeholder="+91 XXXXX XXXXX" required />
          </div>
          <div class="form-group">
            <label for="service">Service Required</label>
            <select id="service" name="service">
              <option value="">Select a service…</option>
              <option>Primary Care &amp; Preventive Medicine</option>
              <option>Diabetes &amp; Metabolic Disorders</option>
              <option>Hypertension Management</option>
              <option>Respiratory Conditions</option>
              <option>Infectious Diseases</option>
              <option>Thyroid &amp; Endocrine Disorders</option>
              <option>Geriatric Care</option>
              <option>Women's Health</option>
              <option>Minor Procedures &amp; Wound Care</option>
              <option>General Inquiry</option>
            </select>
          </div>
          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Describe your symptoms or inquiry…"></textarea>
          </div>
          <button type="submit" class="submit-btn">
            <i class="fas fa-paper-plane"></i>&nbsp; Send Message
          </button>
          <?php if ($sent): ?>
          <div class="form-success" style="display:block;">
            <i class="fas fa-check-circle"></i>&nbsp; Thank you! We'll contact you shortly to confirm your appointment.
          </div>
          <?php elseif ($err): ?>
          <div style="color:#e53e3e;font-size:13px;margin-top:12px;padding:12px;background:rgba(229,62,62,0.08);border-radius:8px;">
            <i class="fas fa-exclamation-circle"></i>&nbsp; Please fill in all required fields with valid details.
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo-icon" style="margin-bottom:16px;"><i class="fas fa-stethoscope"></i></div>
        <p>
          Dr. Prasanna Kumar K provides world-class
          comprehensive medical care in Bengaluru. Our experienced team is
          committed to your health and comfort.
        </p>
        <div class="social-links">
          <a href="#" class="social-link" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="https://wa.me/919538099960" class="social-link" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About Us</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#doctors">Our Doctors</a></li>
          <li><a href="#blog">Industrial Health</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Services</h4>
        <ul>
          <li><a href="#services">Primary Care</a></li>
          <li><a href="#services">Diabetes Care</a></li>
          <li><a href="#services">Hypertension</a></li>
          <li><a href="#services">Respiratory Care</a></li>
          <li><a href="#services">Thyroid &amp; Endocrine</a></li>
          <li><a href="#services">Geriatric Care</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Contact Us</h4>
        <div class="footer-contact-item">
          <i class="fas fa-video icon"></i>
          <p>Online Consultations Available</p>
        </div>
        <div class="footer-contact-item">
          <i class="fas fa-envelope icon"></i>
          <p><a href="mailto:docprasanna@yahoo.com">docprasanna@yahoo.com</a></p>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Dr. Prasanna Kumar K. All rights reserved.</p>
      <p>Developed by <a href="#">Clan Labs</a></p>
    </div>
  </div>
</footer>

<!-- Scroll to Top -->
<button class="scroll-top" aria-label="Scroll to top">
  <i class="fas fa-chevron-up"></i>
</button>

<script src="script.js"></script>
</body>
</html>
