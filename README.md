# News Aggregator API

A high-performance Laravel news aggregator designed to deliver a seamless and unified content experience. The platform intelligently fetches articles from multiple, distinct news sources using an extensible adapter pattern, as demonstrated in the `articles:fetch` command. It offers two distinct content delivery methods to optimize user experience:

- **Blazing-Fast Feeds**: For both public and personalized content streams, the API leverages efficient cursor pagination, enabling smooth, infinite-scroll-ready interfaces with exceptional performance.
- **Powerful Search**: A comprehensive search and filtering endpoint is available, utilizing traditional pagination to handle complex queries across various parameters like keywords, dates, sources, and authors.

Built with scalability and performance at its core, this API is the perfect backend for a modern news application.
## 🚀 Installation & Deployment

### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd news-aggregator

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Database Setup
```bash
php artisan migrate
```

### Step 5: Configure API Keys
Add your news API keys to `.env`:
```bash
# NewsAPI Configuration
NEWS_API_KEY=your_newsapi_key
NEWS_API_BASE_URL=https://newsapi.org/v2

# The Guardian Configuration
GUARDIAN_API_KEY=your_guardian_key
GUARDIAN_API_BASE_URL=https://content.guardianapis.com

# The New York Times Configuration
NYT_API_KEY=your_nyt_key
NYT_API_BASE_URL=https://api.nytimes.com/svc/search/v2
```

### Step 6: Register News Adapters
Add new adapters in `AppServiceProvider.php`:
```php
    $adapters = [
        NewsAPIAdapter::class,
        GuardianAdapter::class,
        NYTAdapter::class,
        // add new adapter here
    ];
    
  
});
```

### Step 7: Run the Application
```bash
php artisan serve
```
### Step 7: Run the FetchArticle schedule
```bash
php artisan schedule:work
```

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`

### Articles
- `GET /api/articles` - Search and filter articles support normal pagination
    - Query params: `keyword`, `date`, `date_from`, `date_to`, `category`, `source`, `author`, `per_page`
- `GET /api/articles/user/feed` - Get personalized feed based on user preferences (requires auth)
    - Query params: `per_page`, `cursor`-
`GET /api/articles/public/feed` - Get public feed (not requires auth) 
    - Query params: `per_page`, `cursor`

### Preferences (Public)
- `GET /api/sources` - Get all available sources
- `GET /api/categories` - Get all available categories
- `GET /api/authors` - Get all available authors

### Preferences (Protected)
- `GET /api/user/preferences` - Get current user's preferences
- `POST /api/user/preferences` - Update user preferences
    - Body: `{ "sources": [1,2,3], "categories": [1,2], "authors": [1,2,3] }`
### Health Check
- `GET /api/health` - API health status


## 🌟 Key Strengths & Architecture Highlights

### 1. **Adapter Pattern for News Sources**
The application uses a clean adapter pattern to integrate multiple news APIs seamlessly:
- **Extensible Design**: Add new news sources by implementing the `NewsAdapterInterface`
- **Standardized Data**: All adapters transform source-specific data into a unified format
- **Fault Tolerance**: Each adapter handles errors independently without affecting others
- **Current Integrations**: NewsAPI, The Guardian, The New York Times

### 2. **High-Performance Database Design**
Optimized PostgreSQL schema with strategic indexing:
- **Full-Text Search**: PostgreSQL `tsvector` with GIN index for lightning-fast article searches
- **Composite Indexes**: Optimized for common query patterns (source + date, category + date)
- **Cursor Pagination**: Efficient infinite scroll support for large datasets (implemented in `@DbArticleRepository`)
- **Descending Indexes**: Specialized indexes for feed queries (`published_at DESC, id DESC`)
- **Automatic Search Vector Updates**: Database triggers maintain search indexes automatically

### 3. **Repository Pattern**
Clean separation of data access logic:
- **Interface-Based**: `ArticleRepositoryInterface` allows easy testing and implementation swapping
- **Query Optimization**: Centralized query building with proper joins and eager loading
- **Pagination Strategies**: Both traditional and cursor-based pagination supported

### 4. **RESTful API Design**
Well-structured endpoints with comprehensive filtering:
- **Public Endpoints**: Article search, filtering, and browsing without authentication (@ArticleController)
- **Authenticated Endpoints**: Personalized feeds based on user preferences (@PreferenceController)
- **Token-Based Auth**: Laravel Sanctum for secure API authentication (@AuthController)
- **Flexible Filtering**: Search by keyword, date range, category, source, and author

## 📋 Prerequisites

- **PHP**: 8.2 or higher
- **PostgreSQL**: 13 or higher
- **Composer**: Latest version
- **API Keys**: 
  - NewsAPI (https://newsapi.org/)
  - The Guardian (https://open-platform.theguardian.com/)
  - The New York Times (https://developer.nytimes.com/)



