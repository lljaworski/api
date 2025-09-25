# Frontend Development Instructions

## Overview

This document provides comprehensive instructions for building a modern frontend application that interfaces with our Symfony API Platform backend. The recommended stack uses Next.js with TypeScript and Chakra UI for optimal integration with our API.

## API Documentation

The API documentation is available at: **http://localhost:8000/api/docs**

- **Interactive Documentation**: Swagger UI interface for testing endpoints
- **OpenAPI Specification**: Available at `http://localhost:8000/api/docs.yaml` or `http://localhost:8000/api/docs.json`
- **JSON-LD Context**: Available at `http://localhost:8000/api/contexts/*`

## Recommended Technology Stack

### Core Framework
- **Next.js 14+** (App Router with TypeScript)
  - Server-side rendering capabilities
  - Built-in TypeScript support
  - Excellent developer experience
  - Perfect for API integration

### UI Framework
- **Chakra UI v2**
  - Comprehensive component library
  - Excellent accessibility support
  - TypeScript-first design
  - Powerful theming system
  - Easy customization

### State Management & API Integration
- **TanStack Query (React Query)** - Server state management and caching
- **Axios** - HTTP client for API requests
- **Zod** - Runtime validation and TypeScript schema generation

### Form Handling
- **React Hook Form** - Performant form library
- **@hookform/resolvers** - Validation resolvers for Zod integration

## Project Setup

### 1. Create Next.js Application

```bash
# Navigate to your desired directory (alongside your API project)
cd /Users/lukaszjaworski/development

# Create Next.js app with TypeScript
npx create-next-app@latest api-frontend --typescript --tailwind --eslint --app --src-dir --import-alias "@/*"

cd api-frontend
```

### 2. Install Dependencies

```bash
# Core UI dependencies
npm install @chakra-ui/react @emotion/react @emotion/styled framer-motion

# API and state management
npm install @tanstack/react-query axios react-hook-form @hookform/resolvers zod

# Development dependencies
npm install -D @types/node
```

## Project Structure

```
api-frontend/
├── src/
│   ├── app/
│   │   ├── layout.tsx          # Root layout with providers
│   │   ├── page.tsx            # Home page
│   │   ├── login/
│   │   │   └── page.tsx        # Login page
│   │   ├── dashboard/
│   │   │   └── page.tsx        # Dashboard page
│   │   └── users/
│   │       ├── page.tsx        # Users list page
│   │       └── [id]/
│   │           └── page.tsx    # User detail page
│   ├── components/
│   │   ├── ui/
│   │   │   ├── Layout.tsx      # Main layout component
│   │   │   ├── Navigation.tsx  # Navigation component
│   │   │   ├── LoadingSpinner.tsx
│   │   │   └── ErrorBoundary.tsx
│   │   ├── forms/
│   │   │   ├── LoginForm.tsx   # Authentication form
│   │   │   ├── UserForm.tsx    # User creation/edit form
│   │   │   └── index.ts
│   │   └── users/
│   │       ├── UserList.tsx    # Users table/grid
│   │       ├── UserCard.tsx    # Individual user display
│   │       └── index.ts
│   ├── hooks/
│   │   ├── useAuth.ts          # Authentication hook
│   │   ├── useUsers.ts         # Users data management
│   │   └── index.ts
│   ├── services/
│   │   ├── api.ts              # Axios configuration
│   │   ├── auth.ts             # Authentication services
│   │   └── users.ts            # User-related API calls
│   ├── types/
│   │   ├── user.ts             # User type definitions
│   │   ├── api.ts              # API response types
│   │   └── index.ts
│   ├── utils/
│   │   ├── constants.ts        # App constants
│   │   └── helpers.ts          # Utility functions
│   └── providers/
│       ├── ChakraProvider.tsx  # Chakra UI theme provider
│       └── QueryProvider.tsx   # React Query provider
├── public/
└── package.json
```

## Configuration Examples

### 1. API Client Configuration (`src/services/api.ts`)

```typescript
import axios from 'axios'

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// JWT token interceptor
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)
```

### 2. Type Definitions (`src/types/user.ts`)

```typescript
// Based on your API Platform User entity
export interface User {
  id: number
  username: string
  roles: string[]
  createdAt: string
  updatedAt: string
}

export interface CreateUserRequest {
  username: string
  password: string
  roles: string[]
}

export interface UpdateUserRequest {
  username?: string
  password?: string
  roles?: string[]
}

export interface LoginRequest {
  username: string
  password: string
}

export interface LoginResponse {
  token: string
}

// API Platform collection response
export interface ApiCollection<T> {
  '@context': string
  '@id': string
  '@type': string
  'hydra:member': T[]
  'hydra:totalItems': number
  'hydra:view'?: {
    '@id': string
    '@type': string
    'hydra:first'?: string
    'hydra:last'?: string
    'hydra:next'?: string
    'hydra:previous'?: string
  }
}
```

### 3. Chakra UI Provider (`src/providers/ChakraProvider.tsx`)

```typescript
'use client'

import { ChakraProvider as ChakraUIProvider, extendTheme } from '@chakra-ui/react'
import { ReactNode } from 'react'

const theme = extendTheme({
  colors: {
    brand: {
      50: '#e3f2fd',
      100: '#bbdefb',
      200: '#90caf9',
      300: '#64b5f6',
      400: '#42a5f5',
      500: '#2196f3',
      600: '#1e88e5',
      700: '#1976d2',
      800: '#1565c0',
      900: '#0d47a1',
    },
  },
  config: {
    initialColorMode: 'light',
    useSystemColorMode: false,
  },
})

interface ChakraProviderProps {
  children: ReactNode
}

export function ChakraProvider({ children }: ChakraProviderProps) {
  return <ChakraUIProvider theme={theme}>{children}</ChakraUIProvider>
}
```

### 4. Authentication Hook (`src/hooks/useAuth.ts`)

```typescript
'use client'

import { useState, useEffect } from 'react'
import { loginUser, type LoginRequest } from '@/services/auth'

export interface AuthUser {
  username: string
  roles: string[]
}

export function useAuth() {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      // Decode JWT to get user info (or fetch from API)
      try {
        const payload = JSON.parse(atob(token.split('.')[1]))
        setUser({
          username: payload.username,
          roles: payload.roles || [],
        })
      } catch (error) {
        localStorage.removeItem('auth_token')
      }
    }
    setIsLoading(false)
  }, [])

  const login = async (credentials: LoginRequest) => {
    const { token } = await loginUser(credentials)
    localStorage.setItem('auth_token', token)
    
    // Decode token to get user info
    const payload = JSON.parse(atob(token.split('.')[1]))
    setUser({
      username: payload.username,
      roles: payload.roles || [],
    })
  }

  const logout = () => {
    localStorage.removeItem('auth_token')
    setUser(null)
  }

  return {
    user,
    isLoading,
    isAuthenticated: !!user,
    login,
    logout,
  }
}
```

## API Integration Patterns

### 1. User Service (`src/services/users.ts`)

```typescript
import { apiClient } from './api'
import type { User, CreateUserRequest, UpdateUserRequest, ApiCollection } from '@/types'

export async function getUsers(page = 1, itemsPerPage = 30): Promise<ApiCollection<User>> {
  const response = await apiClient.get('/users', {
    params: { page, itemsPerPage }
  })
  return response.data
}

export async function getUser(id: number): Promise<User> {
  const response = await apiClient.get(`/users/${id}`)
  return response.data
}

export async function createUser(userData: CreateUserRequest): Promise<User> {
  const response = await apiClient.post('/users', userData)
  return response.data
}

export async function updateUser(id: number, userData: UpdateUserRequest): Promise<User> {
  const response = await apiClient.patch(`/users/${id}`, userData, {
    headers: {
      'Content-Type': 'application/merge-patch+json',
    },
  })
  return response.data
}

export async function deleteUser(id: number): Promise<void> {
  await apiClient.delete(`/users/${id}`)
}
```

### 2. React Query Integration (`src/hooks/useUsers.ts`)

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getUsers, getUser, createUser, updateUser, deleteUser } from '@/services/users'
import type { CreateUserRequest, UpdateUserRequest } from '@/types'

export function useUsers(page = 1, itemsPerPage = 30) {
  return useQuery({
    queryKey: ['users', page, itemsPerPage],
    queryFn: () => getUsers(page, itemsPerPage),
  })
}

export function useUser(id: number) {
  return useQuery({
    queryKey: ['users', id],
    queryFn: () => getUser(id),
  })
}

export function useCreateUser() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: createUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })
}

export function useUpdateUser() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateUserRequest }) => 
      updateUser(id, data),
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      queryClient.invalidateQueries({ queryKey: ['users', id] })
    },
  })
}

export function useDeleteUser() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: deleteUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
    },
  })
}
```

## Development Workflow

### 1. Start Development Servers

```bash
# Terminal 1: Start API (from api directory)
cd /Users/lukaszjaworski/development/api
./boot_project.sh --with-server

# Terminal 2: Start Frontend (from frontend directory)
cd /Users/lukaszjaworski/development/api-frontend
npm run dev
```

### 2. Access Points
- **Frontend Application**: http://localhost:3000
- **API Documentation**: http://localhost:8000/api/docs
- **API Endpoints**: http://localhost:8000/api/*

### 3. Environment Variables

Create `.env.local` in your frontend project:

```bash
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

## Best Practices

### 1. Component Design
- Use Chakra UI components consistently
- Implement proper loading states
- Handle error scenarios gracefully
- Follow React best practices for performance

### 2. API Integration
- Always use TypeScript types for API responses
- Implement proper error handling
- Use React Query for caching and synchronization
- Follow RESTful conventions matching your API Platform setup

### 3. Authentication
- Store JWT tokens securely
- Implement proper token refresh logic
- Handle 401/403 responses appropriately
- Protect routes based on user roles

### 4. Form Handling
- Use React Hook Form with Zod validation
- Match validation rules with your API
- Provide clear error messages
- Handle both client and server validation

### 5. State Management
- Use React Query for server state
- Use React hooks for local state
- Minimize prop drilling with proper component structure
- Implement proper loading and error states

## Testing Recommendations

### 1. Unit Tests
- Test components with React Testing Library
- Mock API calls for isolated testing
- Test custom hooks behavior
- Validate form handling logic

### 2. Integration Tests
- Test API integration with mock server
- Test authentication flow
- Validate error handling
- Test user workflows end-to-end

### 3. E2E Tests (Optional)
- Use Playwright or Cypress
- Test critical user journeys
- Validate API integration in real scenarios

## Deployment Considerations

### 1. Build Optimization
- Configure Next.js for production builds
- Optimize bundle size
- Set up proper environment variables
- Configure caching strategies

### 2. API Integration
- Use environment-specific API URLs
- Handle CORS properly
- Implement proper error logging
- Set up monitoring and analytics

## Alternative Technology Stacks

If Next.js + Chakra UI doesn't fit your needs, consider:

### 1. **Vite + React + Chakra UI**
- Faster development build times
- Smaller bundle sizes
- Similar development experience

### 2. **Nuxt.js + Vue + Vuetify**
- If you prefer Vue.js ecosystem
- Similar SSR capabilities
- Excellent TypeScript support

### 3. **SvelteKit + Skeleton UI**
- Minimal JavaScript footprint
- Excellent performance
- Growing ecosystem

## Conclusion

This setup provides a robust, type-safe frontend that perfectly complements your Symfony API Platform backend. The combination of Next.js, TypeScript, and Chakra UI offers excellent developer experience while maintaining high performance and accessibility standards.

For questions or issues, refer to the API documentation at http://localhost:8000/api/docs and the respective framework documentation.