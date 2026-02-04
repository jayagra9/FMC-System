# FMC System (DFAR – IT Division / FMC Unit)

A web-based internal system built to support the **Fisheries Monitoring Centre (FMC)** workflow at the **Department of Fisheries & Aquatic Resources (DFAR), Sri Lanka**.

This project is mainly focused on recording and managing FMC operational logs such as **Border Crossings**, **Services / Installations**, and **Audit Trails** with role-based access.

---

## ✨ Key Features

### ✅ Border Crossings Module
- Add / Edit border crossing records
- Captures operational workflow details (owner informed, 72hr status, investigation, owner call status, text message status, departure cancel date, remarks)
- Supports “Dropdown + Other (type)” inputs for key fields
- Export options (Excel / PDF / Image) *(if enabled on your page)*

### ✅ Services Module
- Add / Edit service records (equipment / installation/service follow-ups)
- Fields such as:
  - Record No (auto-generated)
  - IOM/ZMS
  - IMUL No
  - BT SN
  - Installed Date
  - Home Port
  - Contact Number
  - Current Status
  - Feedback to Call (dropdown + typing)
  - Service Done Date
  - Comments (dropdown + typing)
  - Installation Checklist

### ✅ Audit Logging (Activity Audit)
- Tracks create / update / delete operations (when enabled in pages)
- Stores change history as JSON/text against each record

### ✅ Admin Views
- Admin-only pages to review records + audit history

---

## 🛠 Tech Stack
- **PHP** (PDO)
- **MySQL**
- **HTML / CSS / JS**
- **XAMPP** (recommended for local development)

---

## 📁 Project Setup (Local – XAMPP)

### 1) Clone the repository
```bash
git clone https://github.com/jayagra9/FMC-System.git
