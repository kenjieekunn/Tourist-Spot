# Database Structure Documentation

## 📊 ER Diagram

```
┌─────────────────┐          ┌──────────────────────┐
│     Users       │          │  Municipalities      │
├─────────────────┤          ├──────────────────────┤
│ id (PK)         │          │ id (PK)              │
│ name            │          │ name                 │
│ email           │          │ description          │
│ password        │          │ latitude             │
│ role            │          │ longitude            │
│ is_active       │          │ image_url            │
│ created_at      │          │ created_at           │
└─────────────────┘          └─────────┬────────────┘
                                       │ 1 (One-to-Many)
                                       │
                                       ▼
                          ┌──────────────────────┐
                          │  Tourist Spots       │
                          ├──────────────────────┤
                          │ id (PK)              │
                          │ municipality_id (FK) │
                          │ name                 │
                          │ description          │
                          │ address              │
                          │ latitude             │
                          │ longitude            │
                          │ image_url            │
                          │ status               │
                          │ created_at           │
                          └──────────┬───────────┘
                                     │ 1 (One-to-Many)
                                     │
                                     ▼
                          ┌──────────────────────┐
                          │  Reviews             │
                          ├──────────────────────┤
                          │ id (PK)              │
                          │ tourist_spot_id (FK) │
                          │ user_name            │
                          │ rating               │
                          │ comment              │
                          │ status               │
                          │ created_at           │
                          └──────────────────────┘
```

## 🔑 Relationships

### One-to-Many (1:M)
- **Municipality** → **Tourist Spots** (one municipality has many tourist spots)
- **Tourist Spot** → **Reviews** (one spot has many reviews)

### Foreign Keys
- `tourist_spots.municipality_id` references `municipalities.id`
- `reviews.tourist_spot_id` references `tourist_spots.id`

## 📋 Table Schemas

### users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_email (email)
);
```

### municipalities
```sql
CREATE TABLE municipalities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description LONGTEXT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_name (name)
);
```

### tourist_spots
```sql
CREATE TABLE tourist_spots (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    municipality_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) UNIQUE NOT NULL,
    description LONGTEXT NOT NULL,
    address VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    image_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (municipality_id) REFERENCES municipalities(id) ON DELETE CASCADE,
    INDEX idx_municipality_id (municipality_id),
    INDEX idx_status (status),
    INDEX idx_name (name)
);
```

### reviews
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tourist_spot_id BIGINT UNSIGNED NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    rating INT NOT NULL COMMENT '1-5 star rating',
    comment LONGTEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tourist_spot_id) REFERENCES tourist_spots(id) ON DELETE CASCADE,
    INDEX idx_tourist_spot_id (tourist_spot_id),
    INDEX idx_status (status)
);
```

## 🔍 Query Examples

### Get all tourist spots with their municipality
```sql
SELECT 
    ts.id,
    ts.name as spot_name,
    m.name as municipality_name,
    ts.latitude,
    ts.longitude,
    AVG(r.rating) as average_rating
FROM tourist_spots ts
JOIN municipalities m ON ts.municipality_id = m.id
LEFT JOIN reviews r ON ts.id = r.tourist_spot_id AND r.status = 'approved'
GROUP BY ts.id
ORDER BY ts.created_at DESC;
```

### Get pending reviews with spot details
```sql
SELECT 
    r.id,
    r.user_name,
    r.rating,
    r.comment,
    ts.name as spot_name,
    m.name as municipality_name
FROM reviews r
JOIN tourist_spots ts ON r.tourist_spot_id = ts.id
JOIN municipalities m ON ts.municipality_id = m.id
WHERE r.status = 'pending'
ORDER BY r.created_at DESC;
```

### Count statistics
```sql
SELECT 
    COUNT(DISTINCT m.id) as total_municipalities,
    COUNT(DISTINCT ts.id) as total_spots,
    COUNT(DISTINCT r.id) as total_reviews,
    COUNT(DISTINCT CASE WHEN r.status = 'pending' THEN r.id END) as pending_reviews
FROM municipalities m
LEFT JOIN tourist_spots ts ON m.id = ts.municipality_id
LEFT JOIN reviews r ON ts.id = r.tourist_spot_id;
```

## 🔐 Constraints & Validation

### Data Integrity
- **NOT NULL** constraints on required fields
- **UNIQUE** constraints on name fields (no duplicates)
- **FOREIGN KEY** constraints with CASCADE DELETE
- **ENUM** fields for fixed-value columns (status, role)

### Data Validation
- Latitude range: -90 to 90
- Longitude range: -180 to 180
- Rating range: 1 to 5
- Status values: active/inactive, pending/approved/rejected

## 📈 Performance Optimization

### Indexes
- Email index on users table (login queries)
- Municipality ID index on tourist_spots (foreign key)
- Status index on reviews (filtering by approval status)
- Name indexes for search queries

### Recommended Indexes for Growth
```sql
-- Add if you have large datasets
ALTER TABLE reviews ADD FULLTEXT INDEX ft_comment (comment);
ALTER TABLE tourist_spots ADD FULLTEXT INDEX ft_name (name);
ALTER TABLE municipalities ADD FULLTEXT INDEX ft_name (name);
```

## 🗑️ Data Deletion Rules

When you delete:
- **Municipality** → All tourist spots in it are deleted
- **Tourist Spot** → All reviews for it are deleted
- **User** → Account is disabled (soft delete recommended)

## 📊 Data Migration

If you need to import existing data:

```php
// Laravel migration example
DB::table('municipalities')->insert([
    ['name' => 'Dagupan', 'latitude' => 16.0419, 'longitude' => 120.3372],
    ['name' => 'Lingayen', 'latitude' => 15.8882, 'longitude' => 120.2656],
    // ... more data
]);
```

---

For detailed implementation, see the migration files in `database/migrations/`
