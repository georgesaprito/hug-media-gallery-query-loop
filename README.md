# 🖼️ Hug Media Gallery Query Loop

**A powerful, category-based dynamic gallery block for WordPress that eliminates manual image selection.**

[![GitHub license](https://img.shields.io/github/license/georgesaprito/hug-media-gallery-query-loop?style=flat-square)](https://github.com/georgesaprito/hug-media-gallery-query-loop/blob/main/LICENSE)
[![WordPress Compatibility](https://img.shields.io/badge/wordpress-%3E%3D%206.0-blue?style=flat-square&logo=wordpress)](https://wordpress.org)
[![Dependency](https://img.shields.io/badge/requires-Media%20Library%20Categories-red?style=flat-square)](https://wordpress.org/plugins/wp-media-library-categories/)

---

## 📖 Overview

The **Hug Media Gallery Query Loop** block transforms how you manage portfolios and galleries. Instead of manually picking images for every gallery, this block automates the process by querying your Media Library based on categories. 

Simply tag your images in the Media Library, drop the block onto a page, and let the engine handle the layout, responsiveness, and lightboxes.

> **Note:** This plugin requires the [Media Library Categories](https://wordpress.org/plugins/wp-media-library-categories/) plugin to be installed and activated.

## ✨ Key Features

* **⚡ Automated Workflow:** Galleries update automatically as you add or remove images from a specific Media Category.
* **🎨 4 Premium Layouts:**
    * **Grid:** Uniform squares/rectangles for a clean, organized look.
    * **Masonry:** Pinterest-style columns that preserve natural image aspect ratios.
    * **Tiled:** Dynamic collage-style layouts that span multiple columns/rows.
    * **Fancy:** A high-end "recursive split" layout that fills containers perfectly for artistic portfolios.
* **📱 Smart Responsiveness:** The "Fancy" layout automatically recalculates math on window resize or phone rotation to ensure zero gaps.
* **🔍 Built-in Lightbox:** Optional full-screen zoomable view powered by **PhotoSwipe**.
* **🔢 Advanced Sorting:** Sort by Date, Alphabetical Title, or manual "Menu Order" from the Media Library.

## 🚀 Getting Started

### 1. Preparation
Before using the block, ensure your images are categorized:
1. Navigate to **Media > Library**.
2. Assign a category (e.g., "Kitchens", "Bathrooms") to your images.

### 2.  Usage
1. Search for **"Hug Media Gallery Query Loop"** in the Block Inserter.
2. Select your desired **Media Category** in the sidebar settings.
3. Customize your Layout, Sorting, and Interaction settings in the **Media Gallery Filters** panel.

## 🛠️ Block Settings

| Setting | Description |
| :--- | :--- |
| **Media Category** | Select the source category for the gallery. |
| **Sort By** | Choose between Date, Title, or Menu Order (Numerical). |
| **Layout** | Toggle between Grid, Masonry, Tiled, or Fancy. |
| **Columns** | Adjust from 1–6 columns (Grid/Masonry only). |
| **Resolution** | Select image size (Thumbnail, Medium, Large, Full). |
| **Interaction** | Toggle Lightbox and Image Title displays. |

## 💡 Pro Tips

* **The "Fancy" Layout:** To maintain editor performance, the complex recursive rendering is disabled in the Gutenberg editor. **Preview the live page** to see the final artistic layout.
* **Image Quality:** If images appear blurry in large layouts, increase the **Image Resolution** setting to "Large" or "Full".
* **Manual Ordering:** To use a custom sequence, use the "Order" column in the Media Library and set the block to sort by **Order**.
