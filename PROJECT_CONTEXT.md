# Project Context: NoorGee

## 1\. Project Identity

**NoorGee** is the central hub and digital ecosystem for Nooruddin's professional and personal ventures. It serves as a gateway to specialized platforms, integrating news media expertise with modern IT technologies. The primary domain is [noorgee.com](https://noorgee.com).

## 2\. Project Vision

The core vision is the **synchronization of News Media with IT Technologies**. NoorGee aims to bridge the gap between media workflows and technological utility, providing a unified interface for global commerce and digital tools.

## 3\. Business Goals

* **Global Marketplace**: Operate a curated US E-commerce store ([us.noorgee.com](https://us.noorgee.com)).  
* **Digital Toolkit**: Provide essential utility tools and services (Urdu Keyboard, Name Generator, News Format Maker).  
* **Smart Guidance**: Offer AI-powered shopping advice through English ([blog.noorgee.com](https://blog.noorgee.com)) and Urdu ([blog.noorgee.pk](https://blog.noorgee.pk)) blogs.  
* **Social Impact**: Support charitable missions through the Karsaziyan Welfare Association ([kwa.com.pk](https://kwa.com.pk)).

## 4\. Tech Stack

* **Frontend**: HTML5, CSS3 (Tailwind CSS via CDN), JavaScript (jQuery).  
* **Backend**: Java-based integrations (Planned/Ongoing).  
* **Interactivity**: Custom JavaScript for dynamic elements (Spotlight text, Counters, Menu transitions).  
* **SEO & Analytics**: JSON-LD (Schema.org), Google Analytics, Google Adsense.  
* **Hosting**: cPanel-based shared hosting (Constraint: No Terminal or SSH access).

## 5\. Folder Structure

├── images/             \# Visual assets (Hero sections, users, logos)

│   ├── 1024/           \# Optimized images for standard displays

│   ├── 1920/           \# High-resolution assets

│   └── 2048/           \# Ultra-high-resolution assets

├── js/                 \# Custom JavaScript (custom.251028004146.js)

├── legal-notice/       \# Compliance documentation

├── privacy/            \# Privacy policy pages

├── teachers/           \# Academic and professional listings

├── webcard/            \# Static assets and vendor libraries (Slick slider, fonts)

├── index.html          \# Main landing page hub

├── deploy.php          \# Custom Git deployment tool for cPanel

└── README.md           \# Project overview

## 6\. Coding Standards

* **Clean Architecture**: Separation of concerns between layout (HTML), styling (Tailwind), and logic (JS/Java).  
* **Tailwind First**: Use Tailwind CSS utility classes for all styling needs.  
* **Semantic HTML**: Ensure proper use of tags for accessibility and SEO.  
* **Responsive Design**: Mobile-first approach using Tailwind's responsive prefixes.

## 7\. Git Workflow

* **Repository**: `grapheart365-eng/ng-com`  
* **Process**: Development is done locally or via AI agents, pushed to GitHub, and pulled to the production server.  
* **Tooling**: Due to lack of SSH, a custom `deploy.php` script is used on the server to perform `git pull`, `git fetch`, and `git reset`.

## 8\. Branch Rules

* **Main Branch**: `main-ng` is the primary production branch.  
* **Feature Branches**: New features should be developed on separate branches and merged into `main-ng` via Pull Requests.

## 9\. Deployment Process

1. Commit changes to the GitHub repository.  
2. Access the `deploy.php` tool on the production server.  
3. Authenticate using the secure password.  
4. Execute "Pull" or "Force Pull" to synchronize the production environment with the latest code from `main-ng`.

## 10\. Environment Variables

* Currently managed within PHP configuration or cPanel environment settings.  
* Future Java integrations will require a `.env` or equivalent configuration for API keys and database credentials.

## 11\. Database Structure

* **Current State**: Static site with JSON-LD for data representation.  
* **Future State**: Integration of a database for user management and dynamic content (e.g., MySQL/TiDB as per project scaffold goals).

## 12\. API Architecture

* **External Integrations**: Google Analytics (gtag.js), Google Adsense (amp-auto-ads).  
* **Data Formats**: JSON-LD for structured data and SEO.  
* **Future**: RESTful APIs for Java-based services.

## 13\. UI/UX Standards

* **Language Support**: Native support for English and Urdu (Urdu script).  
* **Branding**: Dark Navy/Slate (\#1F2937) and Bright Cyan/Teal (\#06B6D4).  
* **Typography**: 'Inter' font family for a modern, clean look.  
* **Animations**: Subtle transitions, spotlight text effects, and animated gradients.

## 14\. SEO Rules

* **Structured Data**: Mandatory use of JSON-LD for FAQs, Products, and Organizations.  
* **Meta Tags**: Unique titles and descriptions for every page.  
* **Performance**: Optimize images and use CDNs for faster loading times.

## 15\. Security Rules

* **Access Control**: Secure `deploy.php` with strong authentication.  
* **Compliance**: Maintain up-to-date Privacy Policy and Legal Notices.  
* **Data Protection**: Ensure SSL is active and configured correctly.

## 16\. AI Working Rules

* **Code Generation**: AI should prioritize Tailwind CSS and modern JS practices.  
* **Translation**: All Urdu content must be translated to English for context, but delivered in Urdu script for the final UI.  
* **Documentation**: AI must update `PROJECT_CONTEXT.md` whenever significant architectural changes occur.

## 17\. Do Not Modify

* **Core Branding**: Logos and primary color schemes.  
* **Legal Compliance**: Privacy policy and legal notice sections unless explicitly requested.  
* **Deployment Logic**: The core functions of `deploy.php` without thorough testing.

## 18\. Current Sprint

* Finalizing Project Context documentation.  
* Designing the main website synchronization between Media and IT.  
* Setting up the foundation for Java-based logic.

## 19\. Current Roadmap

1. Complete `PROJECT_CONTEXT.md`.  
2. Enhance UI/UX for the main hub.  
3. Integrate initial Java backend components.  
4. Expand the "Digital Toolkit" with more utility services.

## 20\. Pending Features

* **Financial Insights**: A dedicated section for financial guidance.  
* **Java Backend**: Full integration of Java for dynamic services.  
* **Enhanced Urdu Support**: More localized tools for Urdu speakers.

## 21\. Known Bugs

* **Constraint**: No Terminal/SSH access on hosting makes debugging server-side issues challenging.  
* **Sync**: Occasional delays in Git-to-cPanel synchronization (mitigated by `deploy.php`).

## 22\. Commit Message Rules

* **Format**: `Title: <Short Title> (<=10 words)` followed by an `Extended Description`.  
* **Language**: English (as per GitHub standards).  
* **Context**: Must mention the specific feature or fix implemented.

## 23\. Daily Progress Format

* Updates are tracked via Git commit history and the `deploy.php` history log.  
* Major milestones are recorded in the `README.md` and `PROJECT_CONTEXT.md`.

## 24\. Prompt Templates

* **Urdu Requests**: "Translate to English \-\> Correct/Refine \-\> Provide Answer in Urdu Script."  
* **Code Requests**: "Provide Code \-\> Provide Git Commit Message \-\> Provide Extended Description."

## 25\. AI Memory

This `PROJECT_CONTEXT.md` file serves as the primary long-term memory for the AI agent to maintain consistency across tasks and sessions.  
