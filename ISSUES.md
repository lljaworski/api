### Feature: Define and Utilize API Keys

In the application, I would like to introduce a feature for defining API Keys with the following requirements:

- **Purpose**: Allow API keys to provide access to specific API endpoints.
- **Access Control**: Only users with the **admin role** can generate and use these API keys.
- **Key Association**: Each API key must be associated with a specific user.
- **Endpoint Role**: Define a role for endpoints so that API keys can be restricted to access only certain endpoints.

### Acceptance Criteria:
- The application should include an interface for adding and managing API keys per user.
- Admins should have exclusive rights to create, update, or delete API keys.
- Endpoints must have a role-based access system compatible with the API key functionality.