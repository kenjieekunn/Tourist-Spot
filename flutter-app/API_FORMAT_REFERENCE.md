# API Response Format Reference

This document defines the expected JSON response format for all API endpoints used by the Flutter app.

---

## Base Response Format

All API responses should follow this structure:

```json
{
  "success": true,
  "data": { /* response data */ },
  "message": "Success message (optional)"
}
```

Or for errors:

```json
{
  "success": false,
  "error": "Error message",
  "code": 400
}
```

---

## Endpoint Response Formats

### 1. Get All Municipalities
**Endpoint**: `GET /municipalities`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Aguilar",
      "description": "A beautiful municipality known for its...",
      "image_path": "https://cdn.example.com/aguilar.jpg",
      "latitude": 15.8500,
      "longitude": 120.5167,
      "tourist_spots_count": 12
    },
    {
      "id": 2,
      "name": "Basista",
      "description": "...",
      "image_path": "https://cdn.example.com/basista.jpg",
      "latitude": 15.8333,
      "longitude": 120.4833,
      "tourist_spots_count": 8
    }
    // ... 6 more municipalities (Binmaley, Bugallon, Labrador, Lingayen, Mangatarem, Urbiztondo)
  ]
}
```

---

### 2. Get Tourist Spots by Municipality
**Endpoint**: `GET /municipalities/{id}/tourist-spots`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
      "name": "Poro Point Lighthouse",
      "description": "Historic lighthouse offering panoramic views...",
      "latitude": 16.0167,
      "longitude": 120.5333,
      "price_range": "₱100-₱500",
      "opening_hours": "8:00 AM - 5:00 PM",
      "contact_number": "+63-9123456789",
      "website_url": "https://example.com",
      "is_featured": true,
      "view_count": 2541,
      "municipality": {
        "id": 1,
        "name": "Aguilar"
      },
      "category": {
        "id": 1,
        "name": "Historical Site"
      },
      "images": [
        {
          "id": 1,
          "image_url": "https://cdn.example.com/poro-1.jpg",
          "caption": "Sunrise view"
        },
        {
          "id": 2,
          "image_url": "https://cdn.example.com/poro-2.jpg",
          "caption": "Main building"
        }
      ],
      "amenities": [
        {"id": 1, "name": "Rest Area"},
        {"id": 2, "name": "Souvenir Shop"},
        {"id": 3, "name": "Photo Spot"}
      ],
      "average_rating": 4.5,
      "reviews_count": 127
    },
    // ... more tourist spots
  ]
}
```

---

### 3. Get Single Tourist Spot Details
**Endpoint**: `GET /tourist-spots/{id}`

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 101,
    "name": "Poro Point Lighthouse",
    "description": "A historic lighthouse standing majestically at Poro Point, Lingayen. Built in the 1880s, it serves as both a navigation aid and tourist destination. The 70-meter tall structure offers breathtaking panoramic views of Lingayen Gulf, especially during sunset...",
    "latitude": 16.0167,
    "longitude": 120.5333,
    "price_range": "₱100-₱500",
    "opening_hours": "8:00 AM - 5:00 PM, Closed Mondays",
    "contact_number": "+63-9123456789",
    "website_url": "https://poropoint.example.com",
    "is_featured": true,
    "view_count": 2541,
    "municipality": {
      "id": 1,
      "name": "Lingayen"
    },
    "category": {
      "id": 1,
      "name": "Historical Site"
    },
    "images": [
      {
        "id": 1,
        "image_url": "https://cdn.example.com/poro-1.jpg",
        "caption": "Sunrise view from the top"
      },
      {
        "id": 2,
        "image_url": "https://cdn.example.com/poro-2.jpg",
        "caption": "Main entrance"
      },
      {
        "id": 3,
        "image_url": "https://cdn.example.com/poro-3.jpg",
        "caption": "Interior staircase"
      }
    ],
    "amenities": [
      {
        "id": 1,
        "name": "Rest Area",
        "icon": "chair"
      },
      {
        "id": 2,
        "name": "Souvenir Shop",
        "icon": "shop"
      },
      {
        "id": 3,
        "name": "Guided Tours",
        "icon": "tour"
      },
      {
        "id": 4,
        "name": "Parking",
        "icon": "parking"
      },
      {
        "id": 5,
        "name": "Restroom",
        "icon": "restroom"
      }
    ],
    "reviews": [
      {
        "id": 1,
        "user_name": "Maria Santos",
        "email": "maria@example.com",
        "rating": 5,
        "comment": "Amazing views! The lighthouse is well-maintained and the staff is very friendly.",
        "is_verified": true,
        "helpful_count": 42,
        "images": [
          "https://cdn.example.com/review-1-1.jpg"
        ],
        "created_at": "2024-03-10T10:30:00Z"
      }
    ],
    "average_rating": 4.5,
    "reviews_count": 127
  }
}
```

---

### 4. Get POIs (Points of Interest) Near a Spot
**Endpoint**: `GET /tourist-spots/{id}/pois`

**Response**:
```json
{
  "success": true,
  "data": {
    "Dining": [
      {
        "id": 1,
        "name": "Lingayen Beach Restaurant",
        "type": "restaurant",
        "description": "Fine dining with seafood specialties",
        "latitude": 16.0180,
        "longitude": 120.5340,
        "distance": 0.45,
        "contact_number": "+63-9111111111",
        "website_url": "https://lingayenbeach.example.com",
        "rating": 4.3,
        "review_count": 89
      },
      {
        "id": 2,
        "name": "Café Punto",
        "type": "cafe",
        "description": "Cozy café with local pastries",
        "latitude": 16.0175,
        "longitude": 120.5345,
        "distance": 0.52,
        "contact_number": "+63-9222222222",
        "website_url": null,
        "rating": 4.1,
        "review_count": 45
      }
    ],
    "Essential Services": [
      {
        "id": 3,
        "name": "BDO ATM",
        "type": "atm",
        "description": "24/7 automated teller machine",
        "latitude": 16.0170,
        "longitude": 120.5330,
        "distance": 0.35,
        "contact_number": null,
        "website_url": null,
        "rating": null,
        "review_count": null
      },
      {
        "id": 4,
        "name": "CALTEX Gas Station",
        "type": "gas_station",
        "description": "Full service gas station with convenience store",
        "latitude": 16.0160,
        "longitude": 120.5320,
        "distance": 0.78,
        "contact_number": "+63-9333333333",
        "website_url": null,
        "rating": 4.0,
        "review_count": 102
      },
      {
        "id": 5,
        "name": "SM Pharmacy",
        "type": "pharmacy",
        "description": "Well-stocked pharmacy with licensed pharmacists",
        "latitude": 16.0185,
        "longitude": 120.5350,
        "distance": 0.65,
        "contact_number": "+63-9444444444",
        "website_url": null,
        "rating": 4.2,
        "review_count": 67
      }
    ]
  }
}
```

---

### 5. Get Reviews for a Tourist Spot
**Endpoint**: `GET /tourist-spots/{id}/reviews`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "spot_id": 101,
      "user_name": "Maria Santos",
      "email": "maria@example.com",
      "rating": 5,
      "comment": "Amazing views! The lighthouse is well-maintained and the staff is very friendly. Definitely worth the visit!",
      "is_verified": true,
      "helpful_count": 42,
      "images": [
        {
          "id": 1,
          "image_url": "https://cdn.example.com/review-1-1.jpg"
        }
      ],
      "created_at": "2024-03-10T10:30:00Z"
    },
    {
      "id": 2,
      "spot_id": 101,
      "user_name": "Juan Dela Cruz",
      "email": "juan@example.com",
      "rating": 4,
      "comment": "Good experience overall. The guide was knowledgeable about the history.",
      "is_verified": false,
      "helpful_count": 15,
      "images": [],
      "created_at": "2024-03-08T14:20:00Z"
    }
  ]
}
```

---

### 6. Search Tourist Spots
**Endpoint**: `GET /tourist-spots/search?q={query}`

**Response**: Same format as "Get Tourist Spots by Municipality"

---

### 7. Search POIs by Coordinates
**Endpoint**: `GET /pois/search?latitude={lat}&longitude={lng}&radius_km={radius}&type={optional}`

**Response**: Same format as "Get POIs Near a Spot"

---

### 8. Submit Review
**Endpoint**: `POST /tourist-spots/{id}/reviews`

**Request Body**:
```json
{
  "user_name": "John Doe",
  "email": "john@example.com",
  "rating": 4,
  "comment": "Great place to visit!",
  "images": [
    "https://cdn.example.com/uploaded-image-1.jpg",
    "https://cdn.example.com/uploaded-image-2.jpg"
  ]
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 127,
    "spot_id": 101,
    "user_name": "John Doe",
    "email": "john@example.com",
    "rating": 4,
    "comment": "Great place to visit!",
    "is_verified": false,
    "helpful_count": 0,
    "images": [
      { "id": 101, "image_url": "https://cdn.example.com/uploaded-image-1.jpg" },
      { "id": 102, "image_url": "https://cdn.example.com/uploaded-image-2.jpg" }
    ],
    "created_at": "2024-03-15T16:45:00Z"
  },
  "message": "Review submitted successfully!"
}
```

---

### 9. Upload Review Image
**Endpoint**: `POST /reviews/upload-image`

**Request**: Multipart form data
```
Content-Type: multipart/form-data
Body:
  file: [binary image file]
```

**Response**:
```json
{
  "success": true,
  "data": {
    "image_url": "https://cdn.example.com/reviews/uploaded-image-12345.jpg",
    "image_id": 1001,
    "file_size": 245630
  }
}
```

---

## Data Type Guidelines

### Municipality Object
```
- id: Integer (Primary Key)
- name: String (max 100)
- description: String (max 1000)
- image_path: String (URL)
- latitude: Float/Double (up to 6 decimals)
- longitude: Float/Double (up to 6 decimals)
- tourist_spots_count: Integer
```

### Tourist Spot Object
```
- id: Integer (Primary Key)
- name: String (max 200)
- description: String (max 5000)
- latitude: Float/Double
- longitude: Float/Double
- price_range: String (e.g., "₱100-₱500")
- opening_hours: String(e.g., "8:00 AM - 5:00 PM")
- contact_number: String (optional)
- website_url: String URL (optional)
- is_featured: Boolean
- view_count: Integer (increment on each view)
- average_rating: Float between 0-5
- reviews_count: Integer (count of reviews)
```

### POI Object
```
- id: Integer
- name: String (max 150)
- type: String (restaurant, cafe, atm, gas_station, pharmacy, etc.)
- description: String (max 500)
- latitude: Float/Double
- longitude: Float/Double
- distance: Float (in kilometers)
- contact_number: String (optional)
- website_url: String (optional)
- rating: Float between 0-5 (optional)
- review_count: Integer (optional)
```

### Review Object
```
- id: Integer
- spot_id: Integer (Foreign Key)
- user_name: String (max 100)
- email: String (max 150)
- rating: Integer (1-5)
- comment: String (between 10-1000 chars)
- is_verified: Boolean (whether user has visited)
- helpful_count: Integer (votes)
- images: Array of Image Objects (max 5)
- created_at: ISO 8601 DateTime
```

---

## HTTP Status Codes

- **200**: Success - Request completed successfully
- **400**: Bad Request - Invalid parameters
- **401**: Unauthorized - Authentication required
- **403**: Forbidden - Insufficient permissions
- **404**: Not Found - Resource not found
- **500**: Server Error - Internal server error
- **503**: Service Unavailable - Server temporarily down

---

## Error Response Example

```json
{
  "success": false,
  "error": "Municipality not found",
  "code": 404
}
```

---

## Pagination (if applicable)

For endpoints returning multiple items, include:

```json
{
  "success": true,
  "data": [ /* array of items */ ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 156,
    "last_page": 8
  }
}
```

---

## Authentication (if required)

Add Bearer token to request headers:

```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

---

## Rate Limiting (recommended)

Implement rate limiting headers:

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1629734400
```

---

**Last Updated**: March 15, 2026
**Version**: 1.0
**For Backend Team**: Please ensure API responses match these formats exactly for optimal app functionality.
