import React from 'react';
import { Button, Card, Badge, Input, Select, Spinner, Avatar } from '../components/ui';

/**
 * Demo page showcasing all UI primitive components
 */
const ComponentsDemo: React.FC = () => {
  const selectOptions = [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
    { value: 'option3', label: 'Option 3' },
  ];

  return (
    <div className="min-h-screen bg-mono-gray-50 p-8">
      <div className="max-w-4xl mx-auto space-y-12">
        <h1 className="text-3xl font-bold text-mono-black mb-8">UI Components Demo</h1>

        {/* Button Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Button</h2>
          <div className="space-y-4">
            <div className="flex flex-wrap gap-4">
              <Button variant="primary" size="sm">Primary SM</Button>
              <Button variant="primary" size="md">Primary MD</Button>
              <Button variant="primary" size="lg">Primary LG</Button>
            </div>
            <div className="flex flex-wrap gap-4">
              <Button variant="secondary" size="md">Secondary</Button>
              <Button variant="ghost" size="md">Ghost</Button>
            </div>
            <div className="flex flex-wrap gap-4">
              <Button variant="primary" size="md" disabled>Disabled</Button>
              <Button variant="primary" size="md" loading>Loading</Button>
            </div>
          </div>
        </section>

        {/* Card Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Card</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card>
              <p>Basic card content</p>
            </Card>
            <Card header={<h3 className="text-lg font-medium">Card with Header</h3>}>
              <p>Card with header and body</p>
            </Card>
            <Card
              header={<h3 className="text-lg font-medium">Full Card</h3>}
              footer={<Button variant="primary" size="sm">Action</Button>}
            >
              <p>Card with header, body, and footer</p>
            </Card>
          </div>
        </section>

        {/* Badge Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Badge</h2>
          <div className="flex flex-wrap gap-4">
            <Badge variant="solid" size="sm">Solid SM</Badge>
            <Badge variant="solid" size="md">Solid MD</Badge>
            <Badge variant="solid" size="lg">Solid LG</Badge>
            <Badge variant="outline" size="md">Outline</Badge>
            <Badge variant="ghost" size="md">Ghost</Badge>
          </div>
        </section>

        {/* Input Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Input</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Input label="Basic Input" placeholder="Enter text" />
            <Input label="Input with Helper" placeholder="Enter text" helperText="This is a helper text" />
            <Input label="Input with Error" placeholder="Enter text" error="This field is required" />
          </div>
        </section>

        {/* Select Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Select</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Select label="Basic Select" options={selectOptions} />
            <Select label="Select with Error" options={selectOptions} error="Please select an option" />
          </div>
        </section>

        {/* Spinner Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Spinner</h2>
          <div className="flex items-center gap-8">
            <div className="flex items-center gap-2">
              <Spinner size="sm" />
              <span>Small</span>
            </div>
            <div className="flex items-center gap-2">
              <Spinner size="md" />
              <span>Medium</span>
            </div>
            <div className="flex items-center gap-2">
              <Spinner size="lg" />
              <span>Large</span>
            </div>
          </div>
        </section>

        {/* Avatar Section */}
        <section>
          <h2 className="text-2xl font-semibold text-mono-black mb-4">Avatar</h2>
          <div className="flex items-center gap-8">
            <div className="text-center">
              <Avatar size="sm" fallback="JD" />
              <p className="text-sm mt-2">Small</p>
            </div>
            <div className="text-center">
              <Avatar size="md" fallback="JD" />
              <p className="text-sm mt-2">Medium</p>
            </div>
            <div className="text-center">
              <Avatar size="lg" fallback="JD" />
              <p className="text-sm mt-2">Large</p>
            </div>
            <div className="text-center">
              <Avatar size="md" src="https://via.placeholder.com/40" alt="User" />
              <p className="text-sm mt-2">With Image</p>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
};

export default ComponentsDemo;