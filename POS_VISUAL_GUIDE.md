# POS Dashboard - Complete Visual Guide

## 📸 Full POS Layout

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│  [☰]  🔥 EldoGas POS    [Search Bar]    [Cylinders] [Reports] [Categories] [👤]│
├─────────────────────────────────────────────────────────────┬───────────────────┤
│                                                             │ 🛒 Shopping Cart  │
│  📦 All Products                              45 items     │ 📦 Total: 1,234   │
│                                                             ├───────────────────┤
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │ [Empty cart]      │
│  │ [Image]  │  │ [Image]  │  │ [Image]  │  │ [Image]  │  │ Add products to   │
│  │ ✓ In(50) │  │ ⚠ Low(5) │  │ ✗ Out    │  │ ✓ In(30) │  │ start a sale      │
│  ├──────────┤  ├──────────┤  ├──────────┤  ├──────────┤  │                   │
│  │Category  │  │Category  │  │Category  │  │Category  │  │                   │
│  │LPG 13kg  │  │LPG 6kg   │  │Cooker    │  │Burner    │  │                   │
│  │KSh 1,500 │  │KSh 800   │  │KSh 3,000 │  │KSh 450   │  │                   │
│  │[Add Cart]│  │[Add Cart]│  │[Disabled]│  │[Add Cart]│  │                   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │                   │
│                                                             │                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │                   │
│  │ More...  │  │ Products │  │ Display  │  │ Here...  │  │                   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │                   │
│                                                             │                   │
├─────────────────────────────────────────────────────────────┴───────────────────┤
│ 📦 Inventory Overview  ▼                                                        │
│ 45 products                                                                     │
│ ┌─────────────────────────────────────────────────┐                           │
│ │ [Search products...]                            │                           │
│ │ ┌─────────────────────────────────────────────┐ │                           │
│ │ │ Total Stock: 1,234                          │ │                           │
│ │ └─────────────────────────────────────────────┘ │                           │
│ │ LPG Cylinder 13kg           ✓ 50                │                           │
│ │ LPG Cylinder 6kg            ⚠ 5                 │                           │
│ │ Gas Cooker 2-Burner         ✗ 0                 │                           │
│ │ Gas Burner Single           ✓ 30                │                           │
│ │ ...                                             │                           │
│ └─────────────────────────────────────────────────┘                           │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🎨 Product Card - Detailed View

### After (New Design):
```
┌────────────────────────────┐
│  [Product Image]           │
│  ┌───────────────────┐     │  ← Top-right corner
│  │ ✓ In Stock (50)   │     │
│  └───────────────────┘     │
├────────────────────────────┤
│  Category Tag              │
│  Product Name (Larger)     │
│                            │
│  KSh 1,500      [+]        │
│  [Add to Cart Button]      │
└────────────────────────────┘
```

---

## 🎯 Stock Badge Variations

### High Stock (Green):
```
┌─────────────────┐
│ ✓ In Stock (50) │  #d1fae5 background
└─────────────────┘  #065f46 text
```

### Low Stock (Orange):
```
┌──────────────┐
│ ⚠ Low (5)    │  #fed7aa background
└──────────────┘  #92400e text
```

### Out of Stock (Red):
```
┌──────────────┐
│ ✗ Out        │  #fee2e2 background
└──────────────┘  #991b1b text
```

---

## 📦 Floating Inventory Card

### Expanded State:
```
┌─────────────────────────────────┐
│ 📦 Inventory Overview  ▲        │  ← Click to collapse
│ 45 products                     │
├─────────────────────────────────┤
│ [Search products...]            │
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │ Total Stock: 1,234          │ │  ← Summary
│ └─────────────────────────────┘ │
├─────────────────────────────────┤
│ LPG Cylinder 13kg      ✓ 50    │  ← Click to scroll
│ LPG Cylinder 6kg       ⚠ 5     │
│ Gas Cooker 2-Burner    ✗ 0     │
│ Gas Burner Single      ✓ 30    │
│ ...                            │
└─────────────────────────────────┘
```

---

**Complete visual guide for the new POS stock display system!** 🎉
