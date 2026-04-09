const express    = require('express');
const nodemailer = require('nodemailer');
const cors       = require('cors');

const app  = express();
const PORT = 3000;

// ─── Middleware ───────────────────────────────────────────────────────────────
app.use(cors());                              // Allow requests from file:// and any origin
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ─── Gmail SMTP Transporter ───────────────────────────────────────────────────
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: 'saqibcena99@gmail.com',
    pass: 'yekwegodingzznny'          // Gmail App Password (spaces removed)
  }
});

// Verify connection on startup
transporter.verify(function (error) {
  if (error) {
    console.error('❌ SMTP connection failed:', error.message);
  } else {
    console.log('✅ Gmail SMTP ready — server listening on http://localhost:' + PORT);
  }
});

// ─── Send Email Endpoint ──────────────────────────────────────────────────────
app.post('/send', async (req, res) => {
  const { name, email, subject, message } = req.body;

  // Validate inputs
  if (!name || !email || !subject || !message) {
    return res.status(400).json({ success: false, error: 'All fields are required.' });
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return res.status(400).json({ success: false, error: 'Invalid email address.' });
  }

  const htmlBody = `
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0;">
  <div style="max-width:600px;margin:auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.1);">
    <div style="background:#149ddd;padding:24px 30px;">
      <h2 style="color:#fff;margin:0;font-size:22px;">&#128236; New Portfolio Message</h2>
    </div>
    <div style="padding:30px;">
      <table style="width:100%;border-collapse:collapse;">
        <tr>
          <td style="padding:10px 0;color:#555;font-weight:bold;width:90px;vertical-align:top;">From:</td>
          <td style="padding:10px 0;color:#333;">${name}</td>
        </tr>
        <tr>
          <td style="padding:10px 0;color:#555;font-weight:bold;vertical-align:top;">Email:</td>
          <td style="padding:10px 0;"><a href="mailto:${email}" style="color:#149ddd;">${email}</a></td>
        </tr>
        <tr>
          <td style="padding:10px 0;color:#555;font-weight:bold;vertical-align:top;">Subject:</td>
          <td style="padding:10px 0;color:#333;">${subject}</td>
        </tr>
      </table>
      <hr style="border:none;border-top:1px solid #eee;margin:20px 0;">
      <h3 style="color:#149ddd;margin-top:0;">Message:</h3>
      <p style="color:#444;line-height:1.8;white-space:pre-wrap;">${message}</p>
    </div>
    <div style="background:#f9f9f9;padding:14px 30px;text-align:center;">
      <small style="color:#aaa;">Sent from Saqib Raza — Portfolio Website</small>
    </div>
  </div>
</body>
</html>`;

  try {
    await transporter.sendMail({
      from:    '"Portfolio | Saqib Raza" <saqibcena99@gmail.com>',
      to:      'saqibcena99@gmail.com',
      replyTo: `"${name}" <${email}>`,
      subject: `Portfolio: ${subject}`,
      html:    htmlBody
    });

    console.log(`📧 Email sent from ${name} <${email}> — Subject: ${subject}`);
    res.json({ success: true });

  } catch (err) {
    console.error('❌ Send error:', err.message);
    res.status(500).json({ success: false, error: 'Failed to send email. Please try again.' });
  }
});

// ─── Start Server ─────────────────────────────────────────────────────────────
app.listen(PORT, () => {
  console.log('─────────────────────────────────────────');
  console.log('  Portfolio Email Server');
  console.log('  Running at: http://localhost:' + PORT);
  console.log('  Endpoint:   POST /send');
  console.log('─────────────────────────────────────────');
});
