# Municipality Images - Diagnostic & Fix Guide

## 🔍 Diagnosis Steps

### Step 1: Check if Storage Symlink Exists

**On Windows (PowerShell):**
```powershell
cd admin-system
ls -la public/storage
```

**On Mac/Linux:**
```bash
cd admin-system
ls -la public/storage
```

**Expected Output:**
```
lrwxrwxrwx  1 user  group  ... storage -> ../storage/app/public
```

**If NOT found**, run:
```bash
php artisan storage:link
```

---

### Step 2: Check Database for Image URLs

```sql
SELECT id, name, image_url FROM municipalities;
```

**Expected Output:**
```
id | name | image_url
1  | Dagupan | /storage/municipality-images/abc123.jpg
2  | Lingayen | /storage/municipality-images/def456.jpg
```

**If image_url is NULL:**
- Images weren't uploaded
- Go to Super Admin and upload images

---

### Step 3: Check API Response

```bash
curl http://localhost:8000/api/v1/municipalities
```

**Expected Output:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dagupan",
      "image_url": "/storage/municipality-images/abc123.jpg",
      ...
    }
  ]
}
```

**If image_url is null in response:**
- Database has NULL values
- Need to upload images

---

### Step 4: Check if Files Exist

**On Windows (PowerShell):**
```powershell
ls admin-system/storage/app/public/municipality-images/
```

**On Mac/Linux:**
```bash
ls admin-system/storage/app/public/municipality-images/
```

**Expected Output:**
```
abc123.jpg
def456.jpg
...
```

**If directory is empty:**
- Images not being saved
- Check file permissions

---

### Step 5: Check File Permissions

**On Mac/Linux:**
```bash
chmod -R 755 admin-system/storage/
chmod -R 755 admin-system/public/
```

**On Windows:**
- Right-click folder → Properties → Security
- Ensure "Modify" permission is enabled

---

## 🔧 Common Issues & Fixes

### Issue 1: Storage Symlink Missing

**Symptom:** Images show 404 error

**Fix:**
```bash
cd admin-system
php artisan storage:link
```

**Verify:**
```bash
ls -la public/storage
```

---

### Issue 2: Image URL is NULL in Database

**Symptom:** API returns `"image_url": null`

**Fix:**
1. Go to Super Admin Dashboard
2. Click Municipalities
3. Click Edit on any municipality
4. Upload an image
5. Click Save

**Verify:**
```sql
SELECT image_url FROM municipalities WHERE id = 1;
```

---

### Issue 3: Image Upload Fails

**Symptom:** Upload button doesn't work or shows error

**Causes & Fixes:**

**A. File too large**
- Max size: 2MB
- Compress image and try again

**B. Wrong file type**
- Allowed: JPG, PNG, WebP
- Convert image and try again

**C. Storage directory not writable**
```bash
chmod -R 755 admin-system/storage/
```

**D. Disk full**
```bash
df -h
```

---

### Issue 4: Flutter Shows Placeholder Instead of Image

**Symptom:** Gray placeholder appears instead of image

**Causes & Fixes:**

**A. Image URL is wrong format**
- Should be: `/storage/municipality-images/filename.jpg`
- Check API response

**B. Image file doesn't exist**
```bash
ls admin-system/storage/app/public/municipality-images/
```

**C. Storage symlink broken**
```bash
php artisan storage:link
```

**D. Network issue**
- Check Flutter logs: `flutter run -v`
- Verify API is accessible

---

## 🛠️ Complete Fix Process

### Step 1: Create Storage Symlink

```bash
cd admin-system
php artisan storage:link
```

### Step 2: Fix Permissions

**On Mac/Linux:**
```bash
chmod -R 755 storage/
chmod -R 755 public/
```

**On Windows (PowerShell as Admin):**
```powershell
icacls "admin-system\storage" /grant:r "%USERNAME%:F" /t
icacls "admin-system\public" /grant:r "%USERNAME%:F" /t
```

### Step 3: Upload Images

1. Go to Super Admin Dashboard
2. Click Municipalities
3. For each municipality:
   - Click Edit
   - Upload an image (JPG/PNG, max 2MB)
   - Click Save

### Step 4: Verify Database

```sql
SELECT id, name, image_url FROM municipalities;
```

All should have image_url values.

### Step 5: Test API

```bash
curl http://localhost:8000/api/v1/municipalities
```

All should have image_url in response.

### Step 6: Run Flutter App

```bash
cd flutter-app
flutter clean
flutter run
```

Images should now display!

---

## 📋 Verification Checklist

- [ ] Storage symlink exists: `public/storage → ../storage/app/public`
- [ ] Storage directory is writable: `chmod 755 storage/`
- [ ] Images uploaded in Super Admin
- [ ] Database has image_url values
- [ ] API returns image_url in response
- [ ] Image files exist in `storage/app/public/municipality-images/`
- [ ] Flutter app displays images

---

## 🧪 Testing Each Component

### Test 1: Storage Symlink
```bash
cd admin-system
php artisan storage:link
ls -la public/storage
```
✅ Should show symlink

### Test 2: Database
```sql
SELECT COUNT(*) FROM municipalities WHERE image_url IS NOT NULL;
```
✅ Should return > 0

### Test 3: API
```bash
curl http://localhost:8000/api/v1/municipalities | grep image_url
```
✅ Should show image URLs

### Test 4: File System
```bash
ls admin-system/storage/app/public/municipality-images/
```
✅ Should show image files

### Test 5: Web Access
```bash
curl http://localhost:8000/storage/municipality-images/filename.jpg
```
✅ Should return image (not 404)

### Test 6: Flutter
```bash
cd flutter-app
flutter run -v
```
✅ Should display images

---

## 🚀 Quick Fix (All-in-One)

Run this if you want to fix everything at once:

**On Mac/Linux:**
```bash
cd admin-system

# Create symlink
php artisan storage:link

# Fix permissions
chmod -R 755 storage/
chmod -R 755 public/

# Clear cache
php artisan cache:clear
php artisan config:clear

echo "✅ Storage setup complete!"
```

**On Windows (PowerShell as Admin):**
```powershell
cd admin-system

# Create symlink
php artisan storage:link

# Clear cache
php artisan cache:clear
php artisan config:clear

Write-Host "✅ Storage setup complete!"
```

---

## 📊 Image Flow Diagram

```
Super Admin Uploads Image
    ↓
File saved to: storage/app/public/municipality-images/filename.jpg
    ↓
URL saved to DB: /storage/municipality-images/filename.jpg
    ↓
API returns: { "image_url": "/storage/municipality-images/filename.jpg" }
    ↓
Flutter receives URL
    ↓
Image.network() tries to load: http://localhost:8000/storage/municipality-images/filename.jpg
    ↓
Web server serves file via symlink: public/storage → storage/app/public
    ↓
Image displays ✅
```

---

## 🔗 Symlink Explanation

The symlink makes files in `storage/app/public/` accessible via web:

```
public/storage → ../storage/app/public
```

This allows:
- File: `storage/app/public/municipality-images/abc.jpg`
- Web URL: `http://localhost:8000/storage/municipality-images/abc.jpg`

**Without symlink:** Files exist but aren't accessible via web (404 error)

---

## 💡 Pro Tips

1. **Always create symlink after fresh install**
   ```bash
   php artisan storage:link
   ```

2. **If symlink breaks, recreate it**
   ```bash
   rm public/storage
   php artisan storage:link
   ```

3. **Check permissions if upload fails**
   ```bash
   chmod -R 755 storage/
   ```

4. **Clear cache if changes don't appear**
   ```bash
   php artisan cache:clear
   ```

5. **Test API before testing Flutter**
   ```bash
   curl http://localhost:8000/api/v1/municipalities
   ```

---

## 📞 Still Not Working?

### Debug Checklist

1. **Symlink exists?**
   ```bash
   ls -la admin-system/public/storage
   ```

2. **Files exist?**
   ```bash
   ls admin-system/storage/app/public/municipality-images/
   ```

3. **Database has URLs?**
   ```sql
   SELECT image_url FROM municipalities LIMIT 1;
   ```

4. **API returns URLs?**
   ```bash
   curl http://localhost:8000/api/v1/municipalities | head -50
   ```

5. **Web can access file?**
   ```bash
   curl http://localhost:8000/storage/municipality-images/filename.jpg
   ```

6. **Flutter logs show error?**
   ```bash
   flutter run -v
   ```

---

## ✅ Success Indicators

You'll know it's working when:

✅ `public/storage` symlink exists
✅ Database has image_url values
✅ API returns image_url in response
✅ Image files exist in storage directory
✅ Web can access images via `/storage/...`
✅ Flutter displays images in municipality cards

---

**Status**: Ready to diagnose and fix
