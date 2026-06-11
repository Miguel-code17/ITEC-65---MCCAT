# MCCAT Ordering System - User Manual

## Table of Contents
1. [Customer Guide](#customer-guide)
2. [Admin Guide](#admin-guide)
3. [Common Tasks](#common-tasks)
4. [Troubleshooting](#troubleshooting)

---

## Customer Guide

### Account Management

#### Creating an Account

1. Navigate to the homepage
2. Click "Sign Up" button in the navigation bar
3. Enter the following information:
   - Full Name: Your complete name
   - Email: Valid email address (used for login)
   - Phone: 10-11 digit mobile number
   - Password: Strong password (min 8 characters recommended)
   - Confirm Password: Repeat your password

4. Click "Create Account"
5. You'll receive a confirmation and redirected to login

**Best Practices:**
- Use a strong password with letters, numbers, and symbols
- Keep your email and phone number updated
- Don't share your account with others

#### Logging In

1. Click "Login" in the navigation bar
2. Enter your email address
3. Enter your password
4. Click "Sign In"
5. You'll be redirected to the menu page

#### Logging Out

1. Look for "Logout" in the user menu (top right)
2. Click "Logout"
3. You'll be redirected to the homepage

### Browsing & Ordering

#### Viewing the Menu

1. Click "Menu" in the navigation bar
2. All available food items are displayed with:
   - Item name and description
   - Price in Philippine Pesos (₱)
   - "Add to Cart" button

#### Filtering by Category

1. On the menu page, look for category filters
2. Click on a category (e.g., "Main Course", "Beverages")
3. Menu updates to show only items in that category

#### Adding Items to Cart

1. Find the desired item on the menu
2. Click "Add to Cart" button
3. Enter the quantity desired (1-9999)
4. Item is added to your cart

#### Viewing Your Cart

1. Click the cart icon (top right) or "Order" in menu
2. Your cart displays:
   - Item name
   - Unit price
   - Quantity
   - Line total
   - Cart subtotal

#### Updating Cart Items

1. In the order page, adjust quantities using + or - buttons
2. Or enter desired quantity directly
3. Click "Update" to refresh totals

#### Removing Items

1. In the order page, click "Remove" next to the item
2. Item is deleted from cart immediately

#### Checkout Process

1. Review cart items and quantities
2. Scroll to checkout section
3. Enter delivery information:
   - **Full Name**: Your name
   - **Email**: Your email address
   - **Phone**: Contact number
   - **Address**: Full delivery address
   - **Special Notes**: (Optional) delivery instructions

4. Review pricing:
   - Subtotal: Sum of all items
   - Delivery Fee: ₱50.00 (may vary)
   - Tax: Calculated automatically
   - **Grand Total**: Final amount to pay

5. Select payment method:
   - Cash on Delivery
   - Bank Transfer
   - Credit/Debit Card (if available)

6. Click "Place Order"
7. Confirm order details
8. Order is submitted successfully

#### Order Confirmation

- Confirmation message displayed
- Order ID provided (e.g., #12345)
- Confirmation email sent
- Estimated delivery time: 30-60 minutes

### Viewing Order History

1. Login to your account
2. Click "My Orders" (if available) or return to homepage
3. All your orders displayed with:
   - Order ID
   - Order date and time
   - Items ordered
   - Total amount paid
   - Current status (Pending/Completed/Cancelled)

---

## Admin Guide

### Accessing the Admin Panel

1. Navigate to: `http://localhost/ITEC-65---MCCAT/admin/olap_dashboard.php`
2. Enter admin credentials if prompted
3. Dashboard loads with KPI cards and charts

### Analytics Dashboard

#### Key Performance Indicators (KPIs)

The dashboard displays four main KPIs:

**Total Orders**
- Number of orders placed in the selected period
- Shows business volume

**Total Revenue**
- Sum of all order totals
- Shows financial performance in Philippine Pesos

**Items Sold**
- Total quantity of items across all orders
- Shows product movement

**Average Order Value**
- Mean order total (Total Revenue ÷ Total Orders)
- Shows customer spending patterns

#### Date Filtering

1. Locate the date filter section at the top
2. Set "Start Date" and "End Date"
3. Click "Apply Filter" button
4. All data updates to reflect selected range

**Quick Filters:**
- "Last 7 Days" - Recent week's data
- "Last 30 Days" - Recent month's data

#### Revenue Trend Chart

- **Type**: Line chart
- **X-axis**: Dates
- **Y-axis**: Revenue amount in ₱
- **Insights**: Shows daily revenue patterns and trends

**Interpretation:**
- Upward trend = Growing revenue
- Downward trend = Declining revenue
- Spikes = Peak sales days

#### Order Count Chart

- **Type**: Bar chart
- **X-axis**: Dates
- **Y-axis**: Number of orders
- **Insights**: Shows daily order volume

**Interpretation:**
- Higher bars = Busier days
- Pattern identification = Peak hours/days

#### Top 10 Products Chart

- **Type**: Horizontal bar chart
- **Displays**: Top performing products by revenue
- **Y-axis**: Product names
- **X-axis**: Revenue amount in ₱

**Key Metrics:**
- Product name
- Total revenue generated
- Units sold
- Percentage of total revenue

#### Category Revenue Chart

- **Type**: Doughnut chart
- **Displays**: Revenue distribution by food category
- **Percentages**: Shown on hover

**Insights:**
- Identifies most profitable categories
- Helps with inventory planning
- Guides menu optimization

### Reports & Exports

#### Exporting Analytics

Three export formats available:

**PDF Export**
1. Click "📄 Export PDF" button
2. Professional report generated
3. Opens in browser or downloads
4. Includes:
   - Report title and date
   - KPI summary statistics
   - Detailed data table
   - Summary calculations

**CSV Export**
1. Click "📊 Export CSV" button
2. Comma-separated values file
3. Opens in Excel or text editor
4. Ideal for further analysis or data import

**Excel Export**
1. Click "📈 Export Excel" button
2. Formatted Excel workbook
3. Multiple sheets with organized data
4. Includes formulas for calculations

**Report Contents:**
- Date range covered
- Daily sales breakdown
- Revenue figures
- Order counts
- Summary statistics

### Viewing Orders

#### OLTP Order Management

1. From dashboard, click "View OLTP Orders"
2. Recent orders displayed in table format
3. Columns include:
   - Order ID (clickable for details)
   - Customer name
   - Contact phone
   - Order date/time
   - Order total
   - Current status

#### Filtering Orders

- Filter by date range
- Filter by status (Pending/Completed/Cancelled)
- Search by customer name or phone

#### Viewing Order Details

1. Click on Order ID in the list
2. Order details page shows:
   - Customer information
   - Delivery address
   - Items ordered (with quantities and prices)
   - Pricing breakdown
   - Current status
   - Order timeline

### Business Intelligence & Recommendations

#### Accessing Recommendations

1. From dashboard or via API endpoint:
   ```
   /scripts/business_intelligence.php?action=recommendations
   ```

#### Understanding Recommendations

The system provides insights in several categories:

**Revenue Growth (HIGH PRIORITY)**
- Alert: "Revenue is trending upward"
- Action: Continue current marketing strategies
- Alert: "Revenue is declining"
- Action: Review promotional activities

**Product Optimization (MEDIUM PRIORITY)**
- Alert: "Consider reviewing underperforming items"
- Products: Lists items with low sales
- Action: Consider discounting or removing

**Staff Scheduling (MEDIUM PRIORITY)**
- Alert: Peak hours identified
- Time: Shows specific hour range
- Action: Ensure adequate staffing

**Customer Loyalty (MEDIUM PRIORITY)**
- Alert: Number of repeat customers
- Insight: Opportunity for loyalty program
- Action: Implement rewards program

#### Performance Metrics API

**Endpoint:** `/scripts/business_intelligence.php?action=metrics&days=30`

**Returns:**
- Total orders for period
- Total revenue
- Average daily orders
- Average order value
- Revenue volatility (standard deviation)
- Order completion rate

**Usage:**
- Identify trends
- Compare periods
- Set performance targets

---

## Common Tasks

### Task 1: Place a Quick Order

**Time Required:** 5 minutes

**Steps:**
1. Login to account
2. Click "Menu"
3. Search for "Fried Chicken"
4. Add 2 units to cart
5. Add 1 unit of "Iced Tea"
6. Click "Order"
7. Fill delivery details
8. Confirm payment method
9. Place order
10. Note confirmation number

**Expected Result:** Order confirmation with ID number

### Task 2: Generate Monthly Sales Report

**Time Required:** 2 minutes

**Steps:**
1. Access admin dashboard
2. Set date range to current month (e.g., June 1-30, 2026)
3. Review KPI cards for overview
4. Click "Export PDF"
5. Save report to local computer
6. Open PDF to verify content

**Expected Result:** Professional PDF report with charts and data

### Task 3: Identify Best-Selling Products

**Time Required:** 1 minute

**Steps:**
1. Dashboard loads with Top 10 Products chart
2. Review horizontal bar chart
3. Identify products with highest revenue bars
4. Cross-reference with Category Revenue chart
5. Note findings for business decisions

**Expected Result:** Clear understanding of product performance

### Task 4: Check Peak Sales Hours

**Time Required:** 1 minute

**Steps:**
1. Access admin dashboard
2. Review "Daily Order Count" bar chart
3. Identify days with tallest bars
4. Note time patterns (morning, afternoon, evening)
5. Use insights for staffing

**Expected Result:** Identified peak service times

---

## Troubleshooting

### Issue: Can't Login

**Symptoms:** Error message "Invalid credentials"

**Solutions:**
1. Verify email address is correct (case-insensitive)
2. Check password is entered without extra spaces
3. Confirm caps lock is off
4. Try "Forgot Password" if available
5. Create new account if all else fails

### Issue: Cart Items Disappear

**Symptoms:** Items not in cart after page refresh

**Solutions:**
1. Ensure cookies are enabled in browser
2. Clear browser cache and cookies
3. Try different browser
4. Add items again
5. Complete checkout promptly

### Issue: Dashboard Shows No Data

**Symptoms:** Empty charts and "No data found" messages

**Solutions:**
1. Verify date range includes data
2. Create sample orders first
3. Run ETL process: `php scripts/olap_etl.php`
4. Refresh page (Ctrl+F5)
5. Check browser console for errors

### Issue: Export Button Not Working

**Symptoms:** Export button clicked but nothing happens

**Solutions:**
1. Check pop-up blocker settings
2. Try different browser
3. Verify JavaScript is enabled
4. Clear browser cache
5. Contact system administrator

### Issue: Slow Performance

**Symptoms:** Dashboard or forms load slowly

**Solutions:**
1. Reduce date range for filtering
2. Clear browser cache (Ctrl+Shift+Delete)
3. Close other browser tabs
4. Check internet connection
5. Try accessing during off-peak hours

---

## FAQ

**Q: How long does delivery take?**
A: Standard delivery is 30-60 minutes from order confirmation

**Q: Can I modify my order after placing it?**
A: Contact customer service immediately if needed

**Q: What payment methods are accepted?**
A: Cash on Delivery, Bank Transfer, and Credit/Debit Card (if enabled)

**Q: How often is data updated on the dashboard?**
A: Data is refreshed hourly through the ETL process

**Q: Can I export data in other formats?**
A: Currently supports PDF, CSV, and Excel formats

**Q: How far back does order history go?**
A: All orders are permanently stored and can be viewed anytime

**Q: Is my payment information secure?**
A: Yes, we use industry-standard encryption and security protocols

---

## Contact & Support

For technical support or questions:
- Email: support@mccat.local
- Phone: 0XXXXXXXXX
- Office Hours: 9:00 AM - 5:00 PM (Monday-Friday)

**Include in support requests:**
- Order ID (if applicable)
- Error message
- Steps to reproduce issue
- Browser and device information
