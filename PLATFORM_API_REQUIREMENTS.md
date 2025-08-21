# Platform API Requirements for AdIntel Integration

## Overview
This document outlines all the required APIs and credentials needed to integrate with various social media and advertising platforms for the AdIntel system.

---

## 1. Facebook & Instagram APIs

### **Facebook Marketing API**
- **Purpose**: Ad management, campaign data, insights
- **Required Credentials**:
  - App ID
  - App Secret
  - Access Token (User or System User)
  - Ad Account ID

### **Facebook Graph API**
- **Purpose**: Page management, post publishing, engagement data
- **Required Credentials**:
  - App ID
  - App Secret
  - Page Access Token
  - Page ID

### **Instagram Basic Display API**
- **Purpose**: Profile info, media publishing
- **Required Credentials**:
  - App ID
  - App Secret
  - Access Token
  - Instagram Business Account ID

### **Instagram Graph API**
- **Purpose**: Business account management, insights
- **Required Credentials**:
  - Facebook App ID
  - Facebook App Secret
  - Instagram Business Account ID
  - Page Access Token

### **Required Permissions**:
- `ads_management`
- `ads_read`
- `business_management`
- `pages_manage_posts`
- `pages_read_engagement`
- `instagram_basic`
- `instagram_manage_comments`
- `instagram_manage_insights`

### **Webhook Events**:
- Lead ads submissions
- Page messages
- Instagram comments
- Ad account updates

---

## 2. Google Ads & Analytics APIs

### **Google Ads API**
- **Purpose**: Campaign management, performance data
- **Required Credentials**:
  - Client ID
  - Client Secret
  - Developer Token
  - Customer ID
  - Refresh Token

### **Google Analytics 4 API**
- **Purpose**: Website analytics, conversion tracking
- **Required Credentials**:
  - Service Account JSON
  - Property ID
  - Measurement ID

### **Google Sheets API**
- **Purpose**: Lead data export, reporting
- **Required Credentials**:
  - Service Account JSON
  - Spreadsheet ID
  - Sheet Name

### **Google My Business API**
- **Purpose**: Local business management
- **Required Credentials**:
  - Client ID
  - Client Secret
  - Location ID
  - Access Token

### **Required Scopes**:
- `https://www.googleapis.com/auth/adwords`
- `https://www.googleapis.com/auth/analytics.readonly`
- `https://www.googleapis.com/auth/spreadsheets`
- `https://www.googleapis.com/auth/business.manage`

---

## 3. TikTok for Business API

### **TikTok Marketing API**
- **Purpose**: Ad management, campaign insights
- **Required Credentials**:
  - App ID
  - Secret
  - Access Token
  - Advertiser ID

### **TikTok Content Posting API**
- **Purpose**: Video publishing, content management
- **Required Credentials**:
  - Client Key
  - Client Secret
  - Access Token
  - Open ID

### **Required Permissions**:
- `advertiser.read`
- `advertiser.write`
- `campaign.read`
- `campaign.write`
- `video.publish`
- `user.info.basic`

---

## 4. Twitter (X) API

### **Twitter API v2**
- **Purpose**: Tweet publishing, engagement tracking
- **Required Credentials**:
  - API Key
  - API Secret Key
  - Bearer Token
  - Access Token
  - Access Token Secret

### **Twitter Ads API**
- **Purpose**: Ad campaign management
- **Required Credentials**:
  - Consumer Key
  - Consumer Secret
  - Access Token
  - Access Token Secret
  - Account ID

### **Required Scopes**:
- `tweet.read`
- `tweet.write`
- `users.read`
- `follows.read`
- `offline.access`

---

## 5. LinkedIn APIs

### **LinkedIn Marketing API**
- **Purpose**: Sponsored content, campaign management
- **Required Credentials**:
  - Client ID
  - Client Secret
  - Access Token
  - Account ID (Ad Account URN)

### **LinkedIn Share API**
- **Purpose**: Content publishing, company page management
- **Required Credentials**:
  - Client ID
  - Client Secret
  - Access Token
  - Organization ID

### **Required Permissions**:
- `r_ads`
- `r_ads_reporting`
- `rw_ads`
- `w_member_social`
- `r_organization_social`
- `w_organization_social`

---

## 6. Snapchat Ads API

### **Snapchat Marketing API**
- **Purpose**: Ad management, audience insights
- **Required Credentials**:
  - Client ID
  - Client Secret
  - Access Token
  - Refresh Token
  - Ad Account ID

### **Required Scopes**:
- `snapchat-marketing-api`
- `snapchat-marketing-api-read`
- `snapchat-marketing-api-write`

---

## 7. YouTube Data & Ads APIs

### **YouTube Data API v3**
- **Purpose**: Video management, channel analytics
- **Required Credentials**:
  - API Key
  - Client ID
  - Client Secret
  - Channel ID

### **YouTube Analytics API**
- **Purpose**: Video performance metrics
- **Required Credentials**:
  - Service Account JSON
  - Channel ID

### **Google Ads API (for YouTube Ads)**
- **Purpose**: YouTube ad campaign management
- **Required Credentials**:
  - Same as Google Ads API
  - YouTube Channel ID

### **Required Scopes**:
- `https://www.googleapis.com/auth/youtube`
- `https://www.googleapis.com/auth/youtube.readonly`
- `https://www.googleapis.com/auth/yt-analytics.readonly`

---

## 8. WhatsApp Business API

### **WhatsApp Business Platform**
- **Purpose**: Customer messaging, automated responses
- **Required Credentials**:
  - Phone Number ID
  - WhatsApp Business Account ID
  - Access Token
  - App ID
  - App Secret
  - Webhook Verify Token

### **Meta Business Manager Setup**:
- Business Manager ID
- System User Access Token
- WhatsApp Business Account
- Phone Number Registration

### **Required Permissions**:
- `whatsapp_business_messaging`
- `whatsapp_business_management`

---

## 9. SEMrush API

### **SEMrush API**
- **Purpose**: Keyword research, competitor analysis, market insights
- **Required Credentials**:
  - API Key
  - Account Type (Pro/Guru/Business)

### **Available Endpoints**:
- Domain Analytics
- Keyword Analytics
- Advertising Research
- Market Intelligence
- Content Audit

### **GCC-Specific Features**:
- Regional keyword data for KSA, UAE, Kuwait, Qatar, Bahrain, Oman
- Local competitor analysis
- Arabic keyword research
- Regional search volume data

---

## 10. Additional APIs for Enhanced Functionality

### **Zapier API**
- **Purpose**: Workflow automation, third-party integrations
- **Required Credentials**:
  - API Key
  - Webhook URLs

### **Slack API**
- **Purpose**: Team notifications, alerts
- **Required Credentials**:
  - Bot Token
  - Workspace ID
  - Channel IDs

### **Microsoft Teams API**
- **Purpose**: Team collaboration, notifications
- **Required Credentials**:
  - Application ID
  - Client Secret
  - Tenant ID

---

## Implementation Priority

### **Phase 1 (Critical)**
1. ✅ Facebook Marketing API
2. ✅ Google Ads API
3. ✅ Google Sheets API
4. ✅ WhatsApp Business API

### **Phase 2 (High Priority)**
1. Instagram Graph API
2. TikTok Marketing API
3. SEMrush API
4. LinkedIn Marketing API

### **Phase 3 (Medium Priority)**
1. Twitter API v2
2. Snapchat Ads API
3. YouTube Data API
4. Google Analytics 4 API

### **Phase 4 (Enhancement)**
1. Zapier API
2. Slack API
3. Microsoft Teams API

---

## Security Requirements

### **OAuth 2.0 Implementation**
- Secure token storage
- Automatic token refresh
- Scope-based permissions
- Multi-tenant token isolation

### **Webhook Security**
- Signature verification
- HTTPS endpoints
- Rate limiting
- Request validation

### **Data Protection**
- Encrypted credential storage
- PCI DSS compliance for payment data
- GDPR compliance for EU users
- Regional data residency (GCC requirements)

---

## Rate Limits & Quotas

### **Facebook APIs**
- Marketing API: 200 calls/hour per user
- Graph API: 200 calls/hour per user
- Instagram API: 200 calls/hour per user

### **Google APIs**
- Ads API: 15,000 operations/day
- Sheets API: 300 requests/minute per project
- Analytics API: 50,000 requests/day

### **TikTok API**
- Marketing API: 10,000 requests/day
- Content API: 1,000 requests/day

### **Twitter API**
- API v2: 2,000,000 tweets/month (Basic)
- Ads API: 1,000 requests/15 minutes

### **LinkedIn API**
- Marketing API: 100,000 requests/day
- Share API: 500 requests/day per member

---

## Cost Considerations

### **Paid APIs**
- SEMrush API: $99-$399/month
- Some Google APIs: Pay-per-use
- WhatsApp Business API: $0.005-$0.09 per message

### **Free Tiers**
- Facebook APIs: Free with rate limits
- Twitter API: Free tier available
- YouTube API: Free with quotas

---

## Setup Instructions

### **1. Create Developer Accounts**
- Facebook for Developers
- Google Cloud Console
- TikTok for Business
- Twitter Developer Portal
- LinkedIn Developer Network
- Snapchat Business Manager
- SEMrush API Portal

### **2. Configure OAuth Applications**
- Set redirect URIs
- Configure webhook endpoints
- Set required permissions/scopes
- Generate API credentials

### **3. Environment Configuration**
```env
# Facebook
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_app_secret
FACEBOOK_WEBHOOK_VERIFY_TOKEN=your_verify_token

# Google
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_DEVELOPER_TOKEN=your_developer_token

# TikTok
TIKTOK_APP_ID=your_app_id
TIKTOK_SECRET=your_secret

# Twitter
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret

# LinkedIn
LINKEDIN_CLIENT_ID=your_client_id
LINKEDIN_CLIENT_SECRET=your_client_secret

# Snapchat
SNAPCHAT_CLIENT_ID=your_client_id
SNAPCHAT_CLIENT_SECRET=your_client_secret

# WhatsApp
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id
WHATSAPP_ACCESS_TOKEN=your_access_token

# SEMrush
SEMRUSH_API_KEY=your_api_key
```

### **4. Database Configuration**
- Store encrypted credentials per tenant
- Implement token refresh mechanisms
- Set up webhook logging
- Configure rate limiting

---

## Testing & Validation

### **API Testing Checklist**
- [ ] Authentication flow
- [ ] Token refresh mechanism
- [ ] Rate limit handling
- [ ] Error handling
- [ ] Webhook validation
- [ ] Data synchronization
- [ ] Multi-tenant isolation

### **GCC Market Testing**
- [ ] Arabic content support
- [ ] Regional currency handling
- [ ] Local timezone support
- [ ] Cultural content guidelines
- [ ] Regional compliance requirements

---

This document serves as the complete reference for all API integrations required for the AdIntel platform. Each API should be implemented with proper error handling, rate limiting, and security measures.
