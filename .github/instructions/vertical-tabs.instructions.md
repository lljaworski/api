# Vertical Tabs with Content Styling Instructions

## Overview

This document provides comprehensive instructions for implementing vertical tabs with content areas, following the pattern established in the company creation form at `http://localhost:3000/companies/create`. This pattern creates an organized, user-friendly interface for complex forms with multiple sections.

## UI Pattern Description

The vertical tabs pattern consists of:
- **Left Sidebar**: Vertical tab navigation with clear section labels
- **Main Content Area**: Dynamic content that changes based on selected tab
- **Visual Indicators**: Active tab highlighting and progress indication
- **Responsive Design**: Adapts to different screen sizes gracefully

## Technology Stack Requirements

- **React 18+** with TypeScript
- **Chakra UI v2+** for component library
- **Next.js 14+** (recommended) or React application
- **React Hook Form** for form handling (if tabs contain forms)

## Implementation Guide

### 1. Basic Vertical Tabs Component

Create a reusable vertical tabs component (`src/components/ui/VerticalTabs.tsx`):

```typescript
'use client'

import {
  Box,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  VStack,
  HStack,
  Text,
  Icon,
  useColorModeValue,
} from '@chakra-ui/react'
import { ReactNode } from 'react'
import { IconType } from 'react-icons'

export interface TabItem {
  id: string
  label: string
  icon?: IconType
  content: ReactNode
  isDisabled?: boolean
  badge?: string | number
}

interface VerticalTabsProps {
  tabs: TabItem[]
  defaultIndex?: number
  onChange?: (index: number) => void
  colorScheme?: string
  size?: 'sm' | 'md' | 'lg'
  isLazy?: boolean
}

export function VerticalTabs({
  tabs,
  defaultIndex = 0,
  onChange,
  colorScheme = 'brand',
  size = 'md',
  isLazy = true,
}: VerticalTabsProps) {
  const bgColor = useColorModeValue('white', 'gray.800')
  const borderColor = useColorModeValue('gray.200', 'gray.600')
  const sidebarBg = useColorModeValue('gray.50', 'gray.700')

  const sizeStyles = {
    sm: { minH: '400px', tabMinH: '40px', fontSize: 'sm' },
    md: { minH: '500px', tabMinH: '48px', fontSize: 'md' },
    lg: { minH: '600px', tabMinH: '56px', fontSize: 'lg' },
  }

  const currentSize = sizeStyles[size]

  return (
    <Box
      bg={bgColor}
      borderRadius="lg"
      borderWidth="1px"
      borderColor={borderColor}
      shadow="sm"
      overflow="hidden"
      minH={currentSize.minH}
    >
      <Tabs
        orientation="vertical"
        variant="unstyled"
        defaultIndex={defaultIndex}
        onChange={onChange}
        isLazy={isLazy}
        size={size}
      >
        <HStack spacing={0} align="stretch" h="full">
          {/* Left Sidebar - Tab Navigation */}
          <Box
            bg={sidebarBg}
            borderRightWidth="1px"
            borderRightColor={borderColor}
            minW="280px"
            maxW="320px"
          >
            <TabList
              flexDirection="column"
              alignItems="stretch"
              border="none"
              p={4}
              spacing={2}
            >
              {tabs.map((tab, index) => (
                <Tab
                  key={tab.id}
                  justifyContent="flex-start"
                  textAlign="left"
                  p={4}
                  minH={currentSize.tabMinH}
                  borderRadius="md"
                  fontSize={currentSize.fontSize}
                  fontWeight="medium"
                  isDisabled={tab.isDisabled}
                  _hover={{
                    bg: useColorModeValue(`${colorScheme}.50`, `${colorScheme}.900`),
                  }}
                  _selected={{
                    bg: useColorModeValue(`${colorScheme}.100`, `${colorScheme}.700`),
                    color: useColorModeValue(`${colorScheme}.700`, `${colorScheme}.100`),
                    borderLeftWidth: '3px',
                    borderLeftColor: `${colorScheme}.500`,
                  }}
                  _disabled={{
                    opacity: 0.4,
                    cursor: 'not-allowed',
                  }}
                >
                  <HStack spacing={3} w="full">
                    {tab.icon && (
                      <Icon as={tab.icon} boxSize={5} flexShrink={0} />
                    )}
                    <VStack spacing={0} align="flex-start" flex={1}>
                      <Text lineHeight="short">{tab.label}</Text>
                    </VStack>
                    {tab.badge && (
                      <Box
                        bg={`${colorScheme}.500`}
                        color="white"
                        borderRadius="full"
                        fontSize="xs"
                        fontWeight="bold"
                        px={2}
                        py={1}
                        minW="20px"
                        textAlign="center"
                      >
                        {tab.badge}
                      </Box>
                    )}
                  </HStack>
                </Tab>
              ))}
            </TabList>
          </Box>

          {/* Main Content Area */}
          <Box flex={1} p={0}>
            <TabPanels>
              {tabs.map((tab) => (
                <TabPanel key={tab.id} p={6} h="full">
                  {tab.content}
                </TabPanel>
              ))}
            </TabPanels>
          </Box>
        </HStack>
      </Tabs>
    </Box>
  )
}
```

### 2. Company Form Implementation Example

Create a company form using the vertical tabs pattern (`src/components/forms/CompanyForm.tsx`):

```typescript
'use client'

import {
  Box,
  VStack,
  FormControl,
  FormLabel,
  Input,
  FormErrorMessage,
  Button,
  SimpleGrid,
  Textarea,
  Select,
  Checkbox,
  NumberInput,
  NumberInputField,
  Heading,
  Text,
  Divider,
  useToast,
} from '@chakra-ui/react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import {
  FiBuilding,
  FiMapPin,
  FiMail,
  FiPhone,
  FiFileText,
  FiUsers,
} from 'react-icons/fi'
import { VerticalTabs, TabItem } from '@/components/ui/VerticalTabs'

// Validation schema matching your API
const companySchema = z.object({
  name: z.string().min(1, 'Company name is required').max(255),
  taxId: z.string().optional(),
  email: z.string().email().optional().or(z.literal('')),
  phoneNumber: z.string().optional(),
  addressLine1: z.string().optional(),
  addressLine2: z.string().optional(),
  countryCode: z.string().optional(),
  correspondenceAddressLine1: z.string().optional(),
  correspondenceAddressLine2: z.string().optional(),
  correspondenceCountryCode: z.string().optional(),
  // Add other fields as needed
})

type CompanyFormData = z.infer<typeof companySchema>

interface CompanyFormProps {
  initialData?: Partial<CompanyFormData>
  onSubmit: (data: CompanyFormData) => Promise<void>
  isLoading?: boolean
}

export function CompanyForm({
  initialData,
  onSubmit,
  isLoading = false,
}: CompanyFormProps) {
  const toast = useToast()
  
  const {
    register,
    handleSubmit,
    formState: { errors, isValid },
    watch,
  } = useForm<CompanyFormData>({
    resolver: zodResolver(companySchema),
    defaultValues: initialData,
    mode: 'onChange',
  })

  const handleFormSubmit = async (data: CompanyFormData) => {
    try {
      await onSubmit(data)
      toast({
        title: 'Success',
        description: 'Company saved successfully',
        status: 'success',
        duration: 3000,
      })
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to save company',
        status: 'error',
        duration: 5000,
      })
    }
  }

  // Tab content components
  const BasicInformationTab = (
    <VStack spacing={6} align="stretch">
      <Box>
        <Heading size="md" mb={4}>Basic Information</Heading>
        <Text color="gray.600" mb={6}>
          Enter the basic company details and identification information.
        </Text>
      </Box>

      <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
        <FormControl isInvalid={!!errors.name} isRequired>
          <FormLabel>Company Name</FormLabel>
          <Input {...register('name')} placeholder="Enter company name" />
          <FormErrorMessage>{errors.name?.message}</FormErrorMessage>
        </FormControl>

        <FormControl isInvalid={!!errors.taxId}>
          <FormLabel>Tax ID (NIP)</FormLabel>
          <Input {...register('taxId')} placeholder="0000000000" />
          <FormErrorMessage>{errors.taxId?.message}</FormErrorMessage>
        </FormControl>
      </SimpleGrid>
    </VStack>
  )

  const AddressTab = (
    <VStack spacing={6} align="stretch">
      <Box>
        <Heading size="md" mb={4}>Address Information</Heading>
        <Text color="gray.600" mb={6}>
          Provide the primary business address and correspondence details.
        </Text>
      </Box>

      <Box>
        <Heading size="sm" mb={4}>Primary Address</Heading>
        <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
          <FormControl isInvalid={!!errors.addressLine1}>
            <FormLabel>Address Line 1</FormLabel>
            <Input {...register('addressLine1')} placeholder="Street address" />
            <FormErrorMessage>{errors.addressLine1?.message}</FormErrorMessage>
          </FormControl>

          <FormControl isInvalid={!!errors.addressLine2}>
            <FormLabel>Address Line 2</FormLabel>
            <Input {...register('addressLine2')} placeholder="Apartment, suite, etc." />
            <FormErrorMessage>{errors.addressLine2?.message}</FormErrorMessage>
          </FormControl>

          <FormControl isInvalid={!!errors.countryCode}>
            <FormLabel>Country</FormLabel>
            <Select {...register('countryCode')} placeholder="Select country">
              <option value="PL">Poland</option>
              <option value="DE">Germany</option>
              <option value="FR">France</option>
              {/* Add more countries */}
            </Select>
            <FormErrorMessage>{errors.countryCode?.message}</FormErrorMessage>
          </FormControl>
        </SimpleGrid>
      </Box>

      <Divider />

      <Box>
        <Heading size="sm" mb={4}>Correspondence Address</Heading>
        <Text fontSize="sm" color="gray.600" mb={4}>
          If different from primary address
        </Text>
        <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
          <FormControl isInvalid={!!errors.correspondenceAddressLine1}>
            <FormLabel>Correspondence Address Line 1</FormLabel>
            <Input
              {...register('correspondenceAddressLine1')}
              placeholder="Correspondence street address"
            />
            <FormErrorMessage>
              {errors.correspondenceAddressLine1?.message}
            </FormErrorMessage>
          </FormControl>

          <FormControl isInvalid={!!errors.correspondenceAddressLine2}>
            <FormLabel>Correspondence Address Line 2</FormLabel>
            <Input
              {...register('correspondenceAddressLine2')}
              placeholder="Apartment, suite, etc."
            />
            <FormErrorMessage>
              {errors.correspondenceAddressLine2?.message}
            </FormErrorMessage>
          </FormControl>
        </SimpleGrid>
      </Box>
    </VStack>
  )

  const ContactTab = (
    <VStack spacing={6} align="stretch">
      <Box>
        <Heading size="md" mb={4}>Contact Information</Heading>
        <Text color="gray.600" mb={6}>
          Add contact details for communication and correspondence.
        </Text>
      </Box>

      <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
        <FormControl isInvalid={!!errors.email}>
          <FormLabel>Email Address</FormLabel>
          <Input
            {...register('email')}
            type="email"
            placeholder="company@example.com"
          />
          <FormErrorMessage>{errors.email?.message}</FormErrorMessage>
        </FormControl>

        <FormControl isInvalid={!!errors.phoneNumber}>
          <FormLabel>Phone Number</FormLabel>
          <Input
            {...register('phoneNumber')}
            type="tel"
            placeholder="+48 123 456 789"
          />
          <FormErrorMessage>{errors.phoneNumber?.message}</FormErrorMessage>
        </FormControl>
      </SimpleGrid>
    </VStack>
  )

  // Define tabs
  const tabs: TabItem[] = [
    {
      id: 'basic',
      label: 'Basic Information',
      icon: FiBuilding,
      content: BasicInformationTab,
    },
    {
      id: 'address',
      label: 'Address Details',
      icon: FiMapPin,
      content: AddressTab,
    },
    {
      id: 'contact',
      label: 'Contact Information',
      icon: FiPhone,
      content: ContactTab,
    },
    {
      id: 'documents',
      label: 'Documentation',
      icon: FiFileText,
      content: (
        <VStack spacing={6}>
          <Text>Document management section coming soon...</Text>
        </VStack>
      ),
      isDisabled: true,
    },
  ]

  return (
    <Box as="form" onSubmit={handleSubmit(handleFormSubmit)}>
      <VerticalTabs tabs={tabs} colorScheme="blue" />
      
      {/* Form Actions */}
      <Box mt={6} p={6} borderTopWidth="1px" borderColor="gray.200">
        <HStack spacing={4} justify="flex-end">
          <Button type="button" variant="outline">
            Cancel
          </Button>
          <Button
            type="submit"
            colorScheme="blue"
            isLoading={isLoading}
            isDisabled={!isValid}
          >
            Save Company
          </Button>
        </HStack>
      </Box>
    </Box>
  )
}
```

### 3. Advanced Features

#### 3.1 Tab Validation State

Add validation indicators to tabs:

```typescript
// Enhanced TabItem interface
export interface TabItem {
  id: string
  label: string
  icon?: IconType
  content: ReactNode
  isDisabled?: boolean
  badge?: string | number
  hasError?: boolean
  isCompleted?: boolean
}

// In the Tab component JSX:
<Tab
  // ... other props
  borderLeftColor={
    tab.hasError
      ? 'red.500'
      : tab.isCompleted
      ? 'green.500'
      : `${colorScheme}.500`
  }
>
  <HStack spacing={3} w="full">
    {tab.icon && (
      <Icon
        as={tab.icon}
        boxSize={5}
        color={
          tab.hasError
            ? 'red.500'
            : tab.isCompleted
            ? 'green.500'
            : 'current'
        }
      />
    )}
    {/* ... rest of content */}
  </HStack>
</Tab>
```

#### 3.2 Progress Indicator

Add a progress indicator to show completion:

```typescript
interface VerticalTabsProps {
  // ... other props
  showProgress?: boolean
  completedSteps?: string[]
}

// Progress component
const TabProgress = ({ current, total, completedSteps }: {
  current: number
  total: number
  completedSteps: string[]
}) => (
  <Box p={4} borderBottomWidth="1px">
    <Text fontSize="sm" fontWeight="medium" mb={2}>
      Progress: {completedSteps.length} of {total} completed
    </Text>
    <Progress
      value={(completedSteps.length / total) * 100}
      colorScheme="green"
      size="sm"
      borderRadius="full"
    />
  </Box>
)
```

#### 3.3 Responsive Behavior

Make tabs responsive for mobile devices:

```typescript
import { useBreakpointValue } from '@chakra-ui/react'

export function ResponsiveVerticalTabs(props: VerticalTabsProps) {
  const isMobile = useBreakpointValue({ base: true, md: false })
  
  if (isMobile) {
    return (
      <Tabs variant="enclosed" colorScheme={props.colorScheme}>
        <TabList overflowX="auto" flexWrap="nowrap">
          {props.tabs.map((tab) => (
            <Tab key={tab.id} minW="fit-content">
              <HStack spacing={2}>
                {tab.icon && <Icon as={tab.icon} />}
                <Text>{tab.label}</Text>
              </HStack>
            </Tab>
          ))}
        </TabList>
        <TabPanels>
          {props.tabs.map((tab) => (
            <TabPanel key={tab.id}>{tab.content}</TabPanel>
          ))}
        </TabPanels>
      </Tabs>
    )
  }
  
  return <VerticalTabs {...props} />
}
```

## Styling Guidelines

### 1. Color Scheme
- **Primary**: Use consistent brand colors throughout tabs
- **States**: Different colors for active, hover, disabled, error states
- **Contrast**: Ensure accessibility with proper contrast ratios

### 2. Typography
- **Hierarchy**: Clear font size and weight hierarchy
- **Readability**: Sufficient line height and letter spacing
- **Consistency**: Use design system typography tokens

### 3. Spacing
- **Consistent**: Use spacing scale (4, 8, 16, 24px, etc.)
- **Breathing Room**: Adequate padding in content areas
- **Visual Separation**: Clear separation between sections

### 4. Interactive States
- **Hover**: Subtle background color change
- **Active**: Clear visual indication of selected tab
- **Focus**: Keyboard navigation support with focus indicators
- **Disabled**: Reduced opacity and interaction prevention

## Accessibility Features

### 1. Keyboard Navigation
- Tab key navigation between tabs
- Arrow keys for tab navigation
- Enter/Space to activate tabs

### 2. Screen Reader Support
- Proper ARIA labels and roles
- Announcements for tab changes
- Content structure that makes sense

### 3. Color Accessibility
- High contrast color combinations
- Don't rely solely on color for information
- Test with colorblind simulators

## Best Practices

### 1. Content Organization
- **Logical Flow**: Organize tabs in a logical sequence
- **Clear Labels**: Use descriptive, concise tab labels
- **Progressive Disclosure**: Show more complex options in later tabs

### 2. Performance
- **Lazy Loading**: Load tab content only when needed
- **Memoization**: Prevent unnecessary re-renders
- **Virtualization**: For tabs with heavy content

### 3. User Experience
- **Save Progress**: Preserve form data when switching tabs
- **Validation**: Show validation errors on appropriate tabs
- **Clear Actions**: Obvious save/cancel actions

### 4. Responsive Design
- **Mobile First**: Design for mobile devices first
- **Touch Friendly**: Adequate touch targets on mobile
- **Content Adaptation**: Adjust content layout for different screens

## Integration with Forms

### 1. Form State Management
```typescript
// Use React Hook Form with tab-aware validation
const { register, formState: { errors }, trigger } = useForm({
  mode: 'onChange',
})

// Validate specific tab sections
const validateTab = async (tabId: string) => {
  const fieldsToValidate = getFieldsForTab(tabId)
  return await trigger(fieldsToValidate)
}
```

### 2. Error Handling
- Show error indicators on tabs with validation errors
- Navigate to tabs with errors automatically
- Provide clear error messages within content areas

## Testing Considerations

### 1. Unit Tests
- Test tab navigation behavior
- Validate content rendering
- Test keyboard navigation

### 2. Integration Tests
- Test form submission across tabs
- Validate error handling
- Test responsive behavior

### 3. E2E Tests
- Test complete user workflows
- Validate data persistence
- Test accessibility features

## Conclusion

This vertical tabs pattern provides a clean, organized way to present complex forms and content. The implementation offers flexibility while maintaining consistency and accessibility. Use this pattern for:

- Multi-step forms
- Settings pages
- Data entry workflows
- Dashboard sections

The pattern scales well and can be adapted for various use cases while maintaining the established design language of your application.