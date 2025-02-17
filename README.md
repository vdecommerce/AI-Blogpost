# AI-Generated Blogpost Plugin

A WordPress plugin that automatically generates blog posts using either OpenAI's GPT models or local LM Studio models, with optional DALL-E image generation.

![screenshot](https://github.com/user-attachments/assets/57d70583-90e7-4dea-98bf-2bbecc7684b4)

## ✨ Core Features

- 🤖 Multiple AI providers support:
  - OpenAI GPT-3.5/4
  - Local LM Studio integration
- 🎨 DALL-E image generation
- 🌍 Multi-language support (EN, NL, DE, FR, ES)
- ⏱️ Flexible scheduling (daily/weekly)
- 📊 Built-in logging and monitoring
- 💾 Smart caching system

## 🛠️ Configuration Options

### Text Generation
- Choice between OpenAI or LM Studio
- Temperature control (0.0 - 2.0)
- Max tokens setting
- Custom system role/prompt templates
- Language selection

### OpenAI Settings
- API key configuration
- Model selection (GPT-4, GPT-3.5-turbo)
- Dynamic model list refresh

### LM Studio Settings
- Local API URL (default: http://localhost:1234)
- Connection testing
- Model detection
- Custom parameters

### Image Generation (DALL-E)
- Optional integration
- Multiple sizes (1024x1024, 1792x1024, 1024x1792)
- Quality settings (Standard/HD)
- Style options (Vivid/Natural)
- Custom prompt templates

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+
- OpenAI API key (for OpenAI/DALL-E)
- LM Studio (for local AI)

## 🚀 Installation

1. Upload to `/wp-content/plugins/`
2. Activate the plugin
3. Configure provider settings:
   - For OpenAI: Enter API key
   - For LM Studio: Configure local URL
4. Set posting schedule
5. Add categories for content generation

## 💡 Usage

### Dashboard Features
- Provider selection
- Test post generation
- Status monitoring
- API logs viewing
- Schedule management

### Content Templates
Customize templates for:
- Blog post structure
- Image generation prompts
- Language-specific content

## 🔄 Performance

- Intelligent caching system
- Automatic cache clearing
- Error logging
- Status monitoring
- API usage tracking

## 🆘 Support

Need assistance? Check:
1. [Documentation Wiki](https://github.com/your-repo/wiki)
2. [Issues Page](https://github.com/your-repo/issues)
3. [Discussions](https://github.com/your-repo/discussions)

## 📄 License

MIT License - Copyright (c) 2025 Van Dijken E-Commerce

Permission is granted to use, copy, modify, and distribute this software for any purpose with or without fee, subject to the license conditions.
