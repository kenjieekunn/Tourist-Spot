# Google Maps API Setup Guide

## Get Free Google Maps API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable these APIs:
   - Maps JavaScript API
   - Places API
   - Geocoding API
4. Go to "Credentials" and create an API key
5. (Optional) Restrict the API key to your domain for security

## Add to Your Project

Open your `.env` file and add:

```
GOOGLE_MAPS_API_KEY=your_api_key_here
```

Replace `your_api_key_here` with your actual API key.

## Free Tier Limits

Google Maps offers $200 free credit per month which includes:
- 28,000 map loads
- 40,000 geocoding requests
- 17,000 places autocomplete requests

This is sufficient for most small to medium applications.

## Current Implementation

The system automatically uses Google Maps when the API key is available, otherwise it falls back to OpenStreetMap (Leaflet).

Both create.blade.php and edit.blade.php now support:
- Google Maps with street-level detail
- Search with street names and barangays
- Automatic address resolution
- Zoom level 18 for precise location marking
