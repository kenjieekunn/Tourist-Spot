# 📊 Super Admin Account Structure - Visual Guide

## System Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                    TOURIST SPOT SYSTEM                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  SUPER ADMIN    │
                    │ (1 account)     │
                    │ Full Access     │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
    ┌────────┐          ┌────────┐          ┌────────┐
    │ Dagupan│          │Lingayen│          │Urbiztondo
    │ Admin  │          │ Admin  │          │ Admin
    │(1 acct)│          │(1 acct)│          │(1 acct)
    └────────┘          └────────┘          └────────┘
        │                    │                    │
        ▼                    ▼                    ▼
    ┌────────┐          ┌────────┐          ┌────────┐
    │ Spots  │          │ Spots  │          │ Spots  │
    │Reviews │          │Reviews │          │Reviews │
    └────────┘          └────────┘          └────────┘

    ... (5 more municipalities)
```

---

## Access Control Diagram

```
┌──────────────────────────────────────────────────────────────┐
│                    SUPER ADMIN                               │
│  ✅ View all municipalities                                  │
│  ✅ Create/edit municipalities                               │
│  ✅ Create/manage all admins                                 │
│  ✅ View all tourist spots                                   │
│  ✅ View all reviews                                         │
│  ✅ System settings                                          │
│  ✅ Access logs                                              │
└──────────────────────────────────────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
                ▼             ▼             ▼
        ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
        │ Municipality │ │ Municipality │ │ Municipality │
        │   Admin 1    │ │   Admin 2    │ │   Admin 3    │
        │              │ │              │ │              │
        │ ✅ Edit own  │ │ ✅ Edit own  │ │ ✅ Edit own  │
        │   municipality
        │ ✅ Manage    │ │ ✅ Manage    │ │ ✅ Manage    │
        │   own spots  │ │   own spots  │ │   own spots  │
        │ ✅ View own  │ │ ✅ View own  │ │ ✅ View own  │
        │   reviews    │ │   reviews    │ │   reviews    │
        │              │ │              │ │              │
        │ ❌ View other│ │ ❌ View other│ │ ❌ View other│
        │   municipalities
        │ ❌ Create    │ │ ❌ Create    │ │ ❌ Create    │
        │   admins     │ │   admins     │ │   admins     │
        │ ❌ System    │ │ ❌ System    │ │ ❌ System    │
        │   settings   │ │   settings   │ │   settings   │
        └──────────────┘ └──────────────┘ └──────────────┘
```

---

## Account Creation Flow

```
START
  │
  ▼
┌─────────────────────────────────┐
│ Super Admin Logs In             │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Goes to Users → Create New      │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Fills in Admin Information:     │
│ - Name                          │
│ - Email                         │
│ - Municipality                  │
│ - Role: municipality-admin      │
│ - Temp Password (generated)     │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Clicks "Create Account"         │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ System Creates Account          │
│ - Stores in database            │
│ - Sends email with credentials  │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Municipality Admin Receives      │
│ Email with Credentials          │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Admin Logs In with Temp Password│
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ System Forces Password Change   │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Admin Creates New Password      │
└────────────┬────────────────────┘
             │
             ▼
┌─────────────────────────────────┐
│ Account Activated ✅            │
│ Admin Can Now Manage Municipality
└─────────────────────────────────┘
```

---

## Data Isolation

```
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  MUNICIPALITIES TABLE                                      │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ ID │ Name       │ Admin ID │ ...                    │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ 1  │ Dagupan    │ 2        │ ...                    │ │
│  │ 2  │ Lingayen   │ 3        │ ...                    │ │
│  │ 3  │ Urbiztondo │ 4        │ ...                    │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                             │
│  USERS TABLE                                               │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ ID │ Name      │ Role              │ Municipality_ID │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ 1  │ Super Adm │ admin             │ NULL            │ │
│  │ 2  │ Juan      │ municipality-admin│ 1 (Dagupan)     │ │
│  │ 3  │ Maria     │ municipality-admin│ 2 (Lingayen)    │ │
│  │ 4  │ Pedro     │ municipality-admin│ 3 (Urbiztondo)  │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                             │
│  TOURIST_SPOTS TABLE                                       │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ ID │ Name      │ Municipality_ID │ ...              │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ 1  │ Dagupan   │ 1               │ ...              │ │
│  │ 2  │ Lingayen  │ 2               │ ...              │ │
│  │ 3  │ Urbiztondo│ 3               │ ...              │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                             │
└─────────────────────────────────────────────────────────────┘

When Juan (Admin for Dagupan) logs in:
  ✅ Can see spots where municipality_id = 1
  ❌ Cannot see spots where municipality_id = 2 or 3
```

---

## Login & Access Flow

```
┌──────────────────────────────────────────────────────────────┐
│                    LOGIN PAGE                                │
│  Email: [________________]                                  │
│  Password: [________________]                               │
│  [Login]                                                    │
└────────────┬─────────────────────────────────────────────────┘
             │
             ▼
┌──────────────────────────────────────────────────────────────┐
│ System Checks Credentials                                   │
│ - Email exists?                                             │
│ - Password correct?                                         │
│ - Account active?                                           │
└────────────┬─────────────────────────────────────────────────┘
             │
      ┌──────┴──────┐
      │             │
      ▼             ▼
   INVALID      VALID
      │             │
      ▼             ▼
  ┌─────┐    ┌──────────────────┐
  │Error│    │ Check User Role  │
  └─────┘    └────────┬─────────┘
                      │
              ┌───────┴────────┐
              │                │
              ▼                ▼
          SUPER ADMIN    MUNICIPALITY ADMIN
              │                │
              ▼                ▼
        ┌──────────┐    ┌──────────────────┐
        │Dashboard │    │ Municipality     │
        │- All data│    │ Dashboard        │
        │- Settings│    │ - Own spots only │
        │- Users   │    │ - Own reviews    │
        └──────────┘    └──────────────────┘
```

---

## Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│                  SECURITY LAYERS                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  LAYER 1: AUTHENTICATION                                   │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ Email + Password (hashed)                           │  │
│  │ Account must be active                              │  │
│  │ Temporary password expires                          │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  LAYER 2: AUTHORIZATION                                    │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ Check user role (admin vs municipality-admin)       │  │
│  │ Check municipality assignment                       │  │
│  │ Verify access permissions                           │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  LAYER 3: DATA ISOLATION                                   │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ Query filters by municipality_id                    │  │
│  │ Municipality admin can only see own data            │  │
│  │ Super admin can see all data                        │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
│  LAYER 4: AUDIT LOGGING                                    │
│  ┌─────────────────────────────────────────────────────┐  │
│  │ Log all admin actions                               │  │
│  │ Track login attempts                                │  │
│  │ Monitor data changes                                │  │
│  └─────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Account Lifecycle

```
CREATION
  │
  ├─ Super Admin creates account
  ├─ Temporary password generated
  ├─ Email sent to admin
  └─ Status: PENDING

FIRST LOGIN
  │
  ├─ Admin logs in with temp password
  ├─ System forces password change
  ├─ Admin creates new password
  └─ Status: ACTIVE

ACTIVE USE
  │
  ├─ Admin manages municipality
  ├─ Super Admin monitors activity
  ├─ Regular password updates
  └─ Status: ACTIVE

DEACTIVATION (if needed)
  │
  ├─ Super Admin deactivates account
  ├─ Admin cannot login
  ├─ Data preserved
  └─ Status: INACTIVE

REACTIVATION (if needed)
  │
  ├─ Super Admin reactivates
  ├─ Admin can login again
  ├─ No data loss
  └─ Status: ACTIVE
```

---

## Best Practice Summary

```
┌─────────────────────────────────────────────────────────────┐
│                  BEST PRACTICES                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ DO                          │  ❌ DON'T                │
│  ─────────────────────────────────────────────────────────  │
│  Use strong passwords           │  Share passwords         │
│  Force password change          │  Use simple passwords    │
│  Send via secure channel        │  Send in plain text      │
│  Deactivate unused accounts     │  Delete accounts         │
│  Monitor activity               │  Ignore suspicious acts  │
│  Keep audit logs                │  Store passwords plainly │
│  Verify email addresses         │  Reuse passwords         │
│  Document all changes           │  Allow multiple admins   │
│  Review regularly               │  Leave accounts active   │
│  Use temporary passwords        │  Give full access        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Timeline

```
WEEK 1: SETUP
├─ Create Super Admin account
├─ Set strong password
├─ Document credentials
└─ Test access

WEEK 2-3: CREATE ADMINS
├─ Identify 8 municipality admins
├─ Create accounts one by one
├─ Send credentials securely
└─ Verify each admin can login

WEEK 4: ONGOING
├─ Monitor admin activity
├─ Review access logs
├─ Update passwords as needed
└─ Deactivate unused accounts

ONGOING: MAINTENANCE
├─ Monthly activity review
├─ Quarterly password updates
├─ Annual security audit
└─ Document all changes
```

---

## Quick Reference

```
SUPER ADMIN
├─ 1 account
├─ Full system access
├─ Manages all admins
└─ Views all data

MUNICIPALITY ADMIN (×8)
├─ 1 per municipality
├─ Manages own municipality
├─ Views own data only
└─ Cannot access other municipalities

SECURITY
├─ Strong passwords (12+ chars)
├─ Temporary passwords on creation
├─ Force password change on first login
├─ Deactivate unused accounts
└─ Monitor all activity
```

---

**This is the recommended approach for your system!**
