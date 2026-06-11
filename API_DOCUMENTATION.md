# MCCAT API Documentation

## Base URL
```
http://localhost/ITEC-65---MCCAT/
```

## Authentication
Currently uses session-based authentication. Ensure user is logged in before making authenticated requests.

---

## Endpoints

### Business Intelligence API

#### Get Recommendations

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=recommendations
```

**Description:** Get AI-generated business recommendations based on current data

**Parameters:** None

**Response:**
```json
[
  {
    "type": "POSITIVE|WARNING|SUGGESTION|INFO",
    "category": "Revenue Growth|Product Optimization|Staff Scheduling|Customer Loyalty",
    "message": "Recommendation message text",
    "priority": "HIGH|MEDIUM|LOW"
  }
]
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=recommendations"
```

**Example Response:**
```json
[
  {
    "type": "POSITIVE",
    "category": "Revenue Growth",
    "message": "Revenue is trending upward! Continue current marketing strategies.",
    "priority": "HIGH"
  },
  {
    "type": "SUGGESTION",
    "category": "Product Optimization",
    "message": "Consider reviewing or removing underperforming items: Adobo Special, Thinly Sliced Beef",
    "priority": "MEDIUM"
  }
]
```

---

#### Get Performance Metrics

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=metrics&days=30
```

**Description:** Get key performance metrics for specified period

**Parameters:**
- `days` (integer, optional): Number of days to analyze (default: 30)

**Response:**
```json
{
  "data_points": 30,
  "total_orders": 150,
  "total_revenue": 45000.50,
  "avg_daily_orders": 5.0,
  "avg_order_value": 300.0,
  "peak_daily_revenue": 2500.00,
  "min_daily_revenue": 800.00,
  "revenue_volatility": 567.89,
  "completion_rate": 95.5
}
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=metrics&days=60"
```

---

#### Get Top Products

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=top_products&limit=10
```

**Description:** Get best-performing products by revenue

**Parameters:**
- `limit` (integer, optional): Number of products to return (default: 10, max: 100)

**Response:**
```json
[
  {
    "food_id": 1,
    "food_name": "Fried Chicken",
    "category": "Main Course",
    "total_units": 250,
    "total_revenue": 48750.00,
    "avg_unit_price": 195.00,
    "days_sold": 28
  },
  {
    "food_id": 2,
    "food_name": "Spaghetti Carbonara",
    "category": "Main Course",
    "total_units": 180,
    "total_revenue": 44100.00,
    "avg_unit_price": 245.00,
    "days_sold": 26
  }
]
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=top_products&limit=5"
```

---

#### Get Peak Hours

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=peak_hours
```

**Description:** Get peak sales hours from historical data

**Parameters:** None

**Response:**
```json
[
  {
    "hour_of_day": 12,
    "time_slot": "12:00",
    "avg_orders": 8.5,
    "avg_revenue": 2550.00,
    "peak_revenue": 3200.00,
    "sample_count": 28
  },
  {
    "hour_of_day": 18,
    "time_slot": "18:00",
    "avg_orders": 7.2,
    "avg_revenue": 2160.00,
    "peak_revenue": 2800.00,
    "sample_count": 28
  }
]
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=peak_hours"
```

---

#### Get Revenue Forecast

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=forecast
```

**Description:** Forecast next day revenue using 7-day moving average

**Parameters:** None

**Response:**
```json
{
  "forecast_revenue": 2100.50
}
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=forecast"
```

---

#### Get Revenue Analysis

**Endpoint:**
```
GET /scripts/business_intelligence.php?action=revenue_analysis&days=30
```

**Description:** Detailed revenue analysis with day-over-day changes

**Parameters:**
- `days` (integer, optional): Number of days to analyze (default: 30)

**Response:**
```json
[
  {
    "sales_date": "2026-06-11",
    "total_revenue": 2450.00,
    "total_orders": 10,
    "avg_order_value": 245.00,
    "prev_day_revenue": 2100.00,
    "revenue_change_pct": 16.67
  },
  {
    "sales_date": "2026-06-10",
    "total_revenue": 2100.00,
    "total_orders": 9,
    "avg_order_value": 233.33,
    "prev_day_revenue": 1850.00,
    "revenue_change_pct": 13.51
  }
]
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=revenue_analysis&days=14"
```

---

### Order API

#### Place Order

**Endpoint:**
```
POST /api/place-order.php
```

**Description:** Submit a new food order

**Content-Type:** application/json

**Request Body:**
```json
{
  "customer_name": "Juan Dela Cruz",
  "email": "juan@example.com",
  "phone": "09123456789",
  "address": "123 Main St, Manila",
  "notes": "Ring doorbell twice",
  "items": [
    {
      "food_id": 1,
      "quantity": 2,
      "unit_price": 195.00
    },
    {
      "food_id": 4,
      "quantity": 1,
      "unit_price": 45.00
    }
  ],
  "subtotal": 435.00,
  "delivery_fee": 50.00,
  "tax_amount": 48.85,
  "grand_total": 533.85,
  "payment_method": "cash"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "order_id": 12345,
  "message": "Order placed successfully",
  "confirmation_number": "ORD-2026-06-11-12345"
}
```

**Response (Error):**
```json
{
  "status": "error",
  "message": "Invalid input data",
  "errors": {
    "email": "Invalid email address",
    "phone": "Phone number must be 10-11 digits"
  }
}
```

**Example Request:**
```bash
curl -X POST http://localhost/ITEC-65---MCCAT/api/place-order.php \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "Juan Dela Cruz",
    "email": "juan@example.com",
    "phone": "09123456789",
    "address": "123 Main St, Manila",
    "items": [
      {"food_id": 1, "quantity": 2, "unit_price": 195.00}
    ],
    "grand_total": 435.00
  }'
```

---

### ETL API

#### Run ETL Process

**Endpoint:**
```
GET /scripts/olap_etl.php?run_etl=1
```

**Description:** Trigger data warehouse refresh/population

**Parameters:**
- `run_etl` (boolean): Set to 1 to execute

**Response:**
```json
{
  "status": "success",
  "timestamp": "2026-06-11 14:30:45",
  "message": "ETL process completed successfully",
  "results": {
    "daily_sales": "SUCCESS",
    "product_daily_sales": "SUCCESS",
    "orders_by_hour": "SUCCESS",
    "category_performance": "SUCCESS"
  }
}
```

**Example Request:**
```bash
curl "http://localhost/ITEC-65---MCCAT/scripts/olap_etl.php?run_etl=1"
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 400 | Bad Request (invalid parameters) |
| 401 | Unauthorized (not logged in) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found (endpoint doesn't exist) |
| 500 | Server Error |

### Error Response Format

```json
{
  "status": "error",
  "message": "Human-readable error message",
  "code": "ERROR_CODE",
  "details": {}
}
```

---

## Rate Limiting

API endpoints implement basic rate limiting:
- **Limit**: 5 requests per minute per IP
- **Response**: HTTP 429 if exceeded
- **Reset**: Automatic after timeframe

---

## Data Formats

### Date Format
ISO 8601: `YYYY-MM-DD` (e.g., `2026-06-11`)

### DateTime Format
ISO 8601: `YYYY-MM-DDTHH:MM:SS` (e.g., `2026-06-11T14:30:45`)

### Currency Format
PHP Peso: Decimal with 2 places (e.g., `1234.50`)

### Phone Format
11 digits or E.164 format (e.g., `09123456789` or `+639123456789`)

---

## Authentication Notes

1. User must be logged in (session required)
2. CSRF tokens required for POST requests
3. Sensitive endpoints validate user role
4. Session expires after 1 hour of inactivity

---

## Integration Examples

### JavaScript/Fetch

```javascript
// Get recommendations
fetch('/scripts/business_intelligence.php?action=recommendations')
  .then(response => response.json())
  .then(data => console.log(data))
  .catch(error => console.error('Error:', error));

// Get metrics for last 60 days
fetch('/scripts/business_intelligence.php?action=metrics&days=60')
  .then(response => response.json())
  .then(metrics => {
    console.log('Total Revenue:', metrics.total_revenue);
    console.log('Avg Order Value:', metrics.avg_order_value);
  });

// Place order
fetch('/api/place-order.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    customer_name: 'Juan Dela Cruz',
    email: 'juan@example.com',
    phone: '09123456789',
    address: '123 Main St',
    items: [{food_id: 1, quantity: 2, unit_price: 195}],
    grand_total: 435
  })
})
  .then(response => response.json())
  .then(result => console.log('Order ID:', result.order_id));
```

### PHP

```php
<?php
// Get recommendations
$response = file_get_contents('http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=recommendations');
$recommendations = json_decode($response, true);

// Get performance metrics
$response = file_get_contents('http://localhost/ITEC-65---MCCAT/scripts/business_intelligence.php?action=metrics&days=30');
$metrics = json_decode($response, true);
echo "Total Orders: " . $metrics['total_orders'];
?>
```

---

## Versioning

Current API Version: **1.0**

Future versions will maintain backward compatibility with v1.0 endpoints.

---

## Support

For API-related issues or questions, contact the development team or check the system logs at `/logs/`.
