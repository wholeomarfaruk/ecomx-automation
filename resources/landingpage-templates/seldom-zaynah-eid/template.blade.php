@extends('layouts.app')
@section('meta_data')
    {{-- Basic SEO Meta --}}
    <title>{{ $data->seo->meta_title ?? 'Landing Page' }}</title>

    <meta name="title" content="{{ $data->seo->meta_title ?? 'Landing Page' }}">
    <meta name="description" content="{{ $data->seo->meta_description ?? 'Landing page meta description' }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $data->seo->meta_title ?? 'Landing Page' }}">
    <meta property="og:description" content="{{ $data->seo->meta_description ?? 'Landing page meta description' }}">
    <meta property="og:image" content="{{ $data->seo->meta_image ?? asset('frontend/images/logo.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Seldom Fashion">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $data->seo->meta_title ?? 'Landing Page' }}">
    <meta name="twitter:description" content="{{ $data->seo->meta_description ?? 'Landing page meta description' }}">
    <meta name="twitter:image" content="{{ $data->seo->meta_image ?? asset('frontend/images/logo.png') }}">
@endsection

@push('styles')
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        .navbar-link {
            font-weight: 800;
            font-size: 15px;
        }

        .navbar-link:hover {
            color: green
        }

        .nav-button {
            background: #03be44;
            color: #fff;
        }

        .nav-button:hover {
            color: #fff;
            background: #05a83e;
        }

        /* nav end */
        .titel {
            color: #03be44;
            text-align: center;
            font-weight: bolder;
        }

        .text {
            color: #666;
            font-size: 18px;
            font-weight: 500;
            line-height: 2.2rem;
        }

        .ratting {
            color: #fca903;
        }

        .gold-text {
            color: #fca903;
            font-size: 13px;
        }

        .btn-order {
            background: #03be44;
            color: #fff
        }

        .btn-order:hover {
            background: #05a83e;
            color: #fff
        }

        .btn-whatsapp,
        .btn-tel {
            color: #fff;
            background: #283147;
            font-size: 25px;
        }

        .btn-tel:hover {
            color: #fff;
            background: #1b2234;
        }

        .btn-whatsapp:hover {
            color: #fff;
            background: #1b2234;
        }

        /* list style */

        .list-item li {
            line-height: 2.2rem;
            color: #7e8ba0;
            font-size: 18px;
            font-weight: 500;
            list-style: none;
        }

        .leading-7 {
            line-height: 1.75rem;
        }

        .text-base {
            font-size: 1rem;
            line-height: 1.5rem;
        }

        .gap-y-3 {
            row-gap: .75rem;
        }

        .gap-x-8 {
            -moz-column-gap: 2rem;
            column-gap: 2rem;
        }

        .grid {
            display: grid;
        }

        .mt-10 {
            margin-top: 2.5rem;
        }

        ol,
        ul,
        menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        @media (min-width: 768px) {
            .list-item {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media screen and (max-width: 768px) {
            .list-item {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .list-text-container {
                justify-content: center;
                padding-right: 0;
            }

            .list-text-container h5 {
                text-align: center;
                margin-top: 10px;
            }

            .list-text-container p {
                text-align: start;
            }

        }

        .list-text {
            text-align: end;
        }

        .titel-two {
            text-align: end;
        }

        .list-text,
        .list-text-two {
            color: #737e91;
            font-size: 12px;
            font-weight: 800;
        }

        .list-text-container img {
            border: 3px solid #03be44;
        }

        .section {
            padding-top: 30px;
            padding-bottom: 30px;
            text-align: center;
        }

        .section p {
            color: #7e8ba0;
        }

        .section .service-box span {
            font-size: 30px;
            margin-bottom: 10px;
            background: #d7f3e4c7;
            color: #32d66b;
            padding: 25px;
            border-radius: 20px;
            display: block;
            width: 100px;
            margin: 10px auto;
            margin-bottom: 15px;
        }

        .section h5 {
            color: #243f4e;
            text-align: center;
            font-weight: bolder;
            margin-bottom: 15px;
            display: block;
        }

        .service-main {
            transition: all 0.3s;
            cursor: pointer;
        }

        .service-main:hover {
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
            transition: all 0.5s;
        }

        #banner {
            background: no-repeat 0 0;
            width: 100%;
        }

        /*  */
        section {
            position: relative;
            max-height: 400px;
            padding: 15px;
            padding: 0 70px;
            max-width: 1200px;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .card {
            position: relative;
            background: #dcf0ddb6;
            border-radius: 20px;
            margin: 20px 0;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        section .card .image {
            height: 100px;
            width: 100px;
            border-radius: 50%;
            padding: 3px;
            background: #03be44;
        }

        section .card .image img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
        }

        .card .media-icons i:hover {
            opacity: 1;
        }

        .review-text {
            font-size: 14px;
            color: #737e91;

        }

        .card .rating {
            display: flex;
            align-items: center;
        }

        .card .rating i {
            font-size: 18px;
            margin: 0 2px;
            color: #fca903;
        }

        .card:hover {
            background: #befbc2b6;
        }

        .review-button {
            font-size: 20px;
            border: 0;
            padding: 5px 10px;
            width: auto;
            color: #000;
        }

        .review-button:hover {
            background: #279ff4;
            color: #fff;
        }

        .swiper-pagination {
            position: absolute;

        }

        .swiper-pagination-bullet {
            height: 7px;
            width: 26px;
            border-radius: 25px;
            background: #7d2ae8;
        }

        .swiper-button-next,
        .swiper-button-prev {
            opacity: 0.7;
            color: #7d2ae8;
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            opacity: 1;
            color: #7d2ae8;
        }

        /* Responsive media query code for small screens */
        @media (max-width: 768px) {
            section {
                padding: 15px;
            }

            .swiper-button-next,
            .swiper-button-prev {
                display: none;
            }
        }

        /* accrodion */
        .accordion-text {
            font-size: 14px;
            color: #878686;
        }

        .accordion-titel {
            color: #243f4e;
            font-size: 20px;
            font-weight: bold;
            display: block;
        }

        .qus-ask {
            width: 75%;
        }

        @media screen and (max-width:768px) {
            .qus-ask {
                width: 100%;
            }
        }

        /* Product Gallrey */
        .gallrey {
            /* margin-top: 10px; */
        }

        .gallrey-pic {
            border-radius: 15px;
            border: 3px solid #03be44;
        }

        .gallrey-pic:hover {
            box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;
            transition: all 0.5s;
            transform: scale(1.02);
        }

        @media screen and (max-width:768px) {
            /* .gallrey {
                                                                        height: 65vh;
                                                                        width: 50%;
                                                                        margin-top: 0;
                                                                    } */
        }

        @media screen and (max-width:499px) {
            /* .gallrey {
                                                                        height: 53vh;
                                                                        width: 50%;
                                                                        margin-top: 0;
                                                                    } */
        }

        @media screen and (max-width:380px) {
            /* .gallrey {
                                                                        height: 40vh;
                                                                        width: 50%;
                                                                        margin-top: 0;
                                                                    } */
        }

        .content-site {
            border: 1px solid #b8b7b7;
            border-radius: 10px;
            background: #ebf7ecb6;
            cursor: pointer;
        }

        .content-site:hover {
            background: #d0f7caca;
        }

        .select-box-titel {
            font-size: 18px;
            font-weight: 500;

        }

        .select-box-price,
        .select-box-weight {
            margin-bottom: 0px;
        }

        .p-selected {
            outline: 2px solid #03be44;
        }

        .order-name {
            font-size: 15px;
            font-weight: 600;
            color: #2e3850;
        }

        .some-heading {
            font-size: 1.5rem;
            font-weight: bolder;
            color: #2e3850;
            margin-bottom: 1rem;
        }

        .box label {
            font-weight: 600;
            font-size: 13px;
            color: #54596a;
        }

        .box form input,
        input::placeholder {
            font-size: 13px;
        }

        .select-box option {
            font-size: 13px;
        }

        .order-heading {
            margin-top: 2px;
            border-bottom: 2px dashed #8a8b8d;
        }

        .price-info h5 span {
            color: #737e91;
        }

        .price-info h5 {
            color: #03be44;
            font-size: 15px;
        }

        .price-info {
            border-bottom: 2px dashed #8a8b8d;
        }

        .delete-btn {
            border: 0;
            background: #fff;
            font-size: 18px;
        }

        .delete-btn:hover {
            color: #03be44;
        }

        .price-list {
            font-weight: bold;
            color: #1b2234;
            margin-top: 20px;
        }

        .deli-price-info {
            border-bottom: 2px dashed #8a8b8d;
        }

        .deli-price-text {
            font-size: 15px;
            color: #666;
        }

        .deli-price {
            color: #03be44;
            font-weight: bold;
        }

        .subtotal-price-info {
            border-bottom: 2px dashed #8a8b8d;
        }

        .subtotal-text {
            color: #000;
            font-weight: bold;
            font-size: 15px;
        }

        .subtotal-text-green {
            color: #03be44;
            font-weight: bold;
        }

        .i-text {
            font-size: 13px;
            color: #b7b6b6;
            margin-top: 20px;
            text-align: center;
        }

        .call-to-action {
            margin-top: 5rem;
            background: url(../image/call-to-action-img.jpg) no-repeat 0 0;
            background-size: cover;
            border-radius: 15px;
        }

        .call-to-action-titel {
            font-weight: bold;
            color: #1b2234;
            text-align: 283nter;
            padding-top: 50px;

        }

        .call-to-action-text {
            padding: 20px 0;
            font-size: 14px;
            color: #4a5466;
            text-align: center;
        }

        .section-footer {
            background: #1b2234;
            padding: 10px;
        }

        .divider {
            color: #d1d1d1;
        }

        .footer-content {
            margin-top: 50px;
            margin-bottom: 50px;
            gap: 7rem;
        }

        .footer-text {
            color: #9ba2b0;
            font-size: 13px;
            padding-top: 10px;
            line-height: 1.5rem;
        }

        .footer-text-contact {
            color: #9ba2b0;
            font-size: 13px;
            padding-top: 10px;
        }

        .footer-text-contact:hover {
            color: #03be44;
        }

        .footer-titel {
            color: #c9cbd1;
            font-weight: bold;
            font-size: 15px;
        }

        .social-icon a {
            margin-top: 20px;
            color: #9ba2b0;
        }

        .social-icon a i {
            font-size: 20px;
        }

        .icon-group {
            background: #504f4f;
            padding: 10px;
            border-radius: 10px;
        }

        .icon-facebook {
            background: #504f4f;
            padding: 10px 16px;
            border-radius: 10px;
        }

        .icon-messenger {
            background: #504f4f;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .icon-phone {
            background: #504f4f;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .icon-whatsapp {
            background: #504f4f;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .icon-mail {
            background: #504f4f;
            padding: 10px 12px;
            border-radius: 10px;
        }

        .social-icon a:hover {
            background: #03be44;
            color: #fff;
        }

        @media screen and (max-width:768px) {
            .footer-content {
                gap: 2rem
            }

            .order-info-box {
                padding: 0px 10px;
            }
        }

        .order-info-box {
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            padding: 40px 100px;
        }

        .thanks {
            font-size: 13px;
            color: #4a5466;
            font-weight: bold;
        }

        .order-some-text {
            color: #7e8ba0;
            font-size: 14px;
            line-height: 25px;
        }

        .order-id,
        .date {
            color: #54596a;
            font-weight: bold;
        }

        .order-number,
        .date-time {
            color: #9ba2b0;
            font-weight: bold;
            font-size: 14px;
        }

        .order-card {
            margin-top: 35px;
            border-top: 1px solid #5c5c5c36;
            border-bottom: 1px solid #5c5c5c36;
        }

        .order-card img {
            width: 15%;
            border-radius: 15px;
        }

        .order-product-name {
            font-size: 14px;
            color: #54596a;
            font-weight: bold;
        }

        .order-product-weight {
            color: #9ba2b0;
            font-size: 13px;
        }

        .order-product-price {
            font-size: 13px;
            color: #54596a;
        }

        .delivery-info {
            text-transform: uppercase;
            font-weight: bold;
            color: #9ba2b0;
            font-size: 13px;
        }

        .delivery-info-total {
            text-transform: uppercase;
            font-weight: bold;
            color: #283147;
            font-size: 14px;
        }

        .order-price-unit {
            color: #283147;
            font-size: 13px;
        }

        .order-price-unit-total {
            font-weight: bold;
            color: #283147;
            font-size: 14px;
        }

        .some-titel {
            color: #9ba2b0;
            font-size: 14px;
            font-weight: bold;
        }

        .person-name,
        .delivery-type {
            color: #504f4f;
            font-weight: bold;
            font-size: 14px;
        }

        .order-address,
        .delivery-notice {
            color: #7e8ba0;
            font-size: 13px;
        }

        .buttom-notice {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #4d596e;
        }

        #order .product-list .col-md-6 {
            width: 48%;
        }


        .facebook-embade {
            margin: 0 auto;
            width: 100%;
            height: 600px;
            border: none;
            overflow: hidden;
            visibility: visible;
        }

        @media screen and (max-width:768px) {
            .order-info-box {
                padding: 40px 20px;
            }

            .order-card img {
                width: 30%;
                border-radius: 15px;
            }

            .facebook-embade {
                width: 100%;
                height: 600px;
            }

        }

        .dressSwiper .swiper-slide img {
            border-radius: 12px;
            object-fit: cover;
        }

        .review-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .review-card .image img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffc107;
            /* optional golden border */
        }

        .review-card .name-button .name {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .review-card .rating i {
            color: #ffc107;
            margin-right: 3px;
        }

        .review-card .review-text {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #555;
            margin-top: 8px;
        }

        .review-button {
            background: #4267B2;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            font-size: 0.85rem;
        }

        .review-button:hover {
            background: #365899;
        }

        #lightgallery {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 10px;
        }

        @media screen and (max-width:768px) {
            #lightgallery {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        .swiper {

            height: auto;

        }

        .video-portrait {
            width: 100%;
            max-width: 420px;
            /* optional limit */
            margin: 0 auto;
            aspect-ratio: 9 / 16;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 15px 3px rgba(0, 0, 0, 0.1);

        }

        .video-portrait .plyr,
        .video-portrait video {
            width: 100%;
            height: 100%;


        }
    </style>
    @if (isset($data->hero->video->thumbnail) && $data->hero->video->thumbnail && !empty($data->hero->video->thumbnail))
        <link rel="preload" as="image" href="{{ $data->hero->video->thumbnail }}" fetchpriority="high">
    @endif
@endpush
@section('content')
    <div class="container mt-2 mt-md-5 justify-content-center">
        <div class="  d-block d-md-flex gap-2">

            <!-- facebook content -->
            <div class="facebook-content col-md-4  order-1 order-md-2 ">
                <div class="d-flex justify-content-center">
                    <div class="video-portrait">
                        <video id="player" playsinline controls preload="none"
                            @if (isset($data->hero->video->thumbnail) && $data->hero->video->thumbnail && !empty($data->hero->video->thumbnail)) poster="{{ $data->hero->video->thumbnail }}" @endif>
                            <source src="{{ $data->hero->video->embed_code }}" type="video/mp4" />
                        </video>
                    </div>




                    {{-- <iframe  width="100%" height="auto" src="https://www.youtube.com/embed/meSt-we79FU" title="&quot;Blush Glow&quot; 3 Piece Dress" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe> --}}
                    {{-- <iframe class="facebook-embade rounded" name="f19677c65523e8af2" width="100%" height="auto"
                        data-testid="fb:video Facebook Social Plugin" title="fb:video Facebook Social Plugin" frameborder="0"
                        allowtransparency="true" allowfullscreen="true" scrolling="no"
                        allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                        src="https://www.facebook.com/plugins/video.php?href={{ $data->hero->video->embed_code }}&show_text=false&autoplay=false">
                    </iframe> --}}

                </div>
            </div>

            <div class="col-md-8 order-2 order-md-1">
                <h2 class="titel text-start fw-bolder mt-3 mt-md-0 mb-2 mb-md-5">
                    {{ $data->hero->title }}
                    {{-- ✨🌙 Zaynah — Our First Eid Icon 🌙✨ --}}
                </h2>

                <p class="text text-wrap text-left">
                    {{ nl2br(e($data->hero->description)) }}
                </p>

                <ul role="list"
                    class="mt-10 grid grid-col-2 gap-x-8 gap-y-3 text-base leading-7 sm:grid-col-3 list-item">
                    @foreach ($data->hero->features as $feature_item)
                        @if (!empty($feature_item))
                            <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                                <i class="fa-solid fa-circle-check me-3"></i>{{ $feature_item }}
                            </li>
                        @endif
                    @endforeach
                    {{-- <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Premium Fabric
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Elegant Floral
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Kashmiri Silk
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Soft Cotton
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Classy Look
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Eid Special
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Comfort Fit
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Limited Stock
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Fast Delivery
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Cash Delivery
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Luxury Finish
                    </li>

                    <li class="flex gap-x-3 text-xl text-slate-400 font-medium items-center">
                        <i class="fa-solid fa-circle-check me-3"></i>Timeless Style
                    </li> --}}

                </ul>

                <div class="ratting d-flex mt-4 fs-4">
                    <i class="fa-solid fa-star me-1"></i>
                    <i class="fa-solid fa-star me-1"></i>
                    <i class="fa-solid fa-star me-1"></i>
                    <i class="fa-solid fa-star me-1"></i>
                    <i class="fa-solid fa-star me-1"></i>
                </div>

                <p class="gold-text mt-2">
                    Limited Eid Collection 💛 <br>
                    সারাদেশে Cash on Delivery ও Fast Delivery সুবিধা
                </p>

                <div class="d-flex gap-2">
                    <a href="#order" class="btn btn-order">
                        <i class="fa-solid fa-cart-shopping me-2"></i>অর্ডার করুন
                    </a>

                    <a href="https://wa.me/8801622351266" target="_blank" class="btn btn-whatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>

                    <a href="https://m.me/seldombd" target="_blank" class="btn btn-tel">
                        <i class="fa-brands fa-facebook-messenger"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
    <div class="py-5">
        <div class="container">
            <div class="row">
                <img src="{{ $data->promo_banner_top->image }}" loading="lazy" alt="Offer Banner" />
            </div>
        </div>
    </div>

    <div class="container list-text-container mt-5" id="difference">
        <div class="swiper dressSwiper">
            <div class="swiper-wrapper">
                @foreach ($data->product_gallery->images as $slider_image)
                    @if ($slider_image && $slider_image != null && !empty($slider_image))
                        <div class="swiper-slide">
                            <img src="{{ $slider_image }}" loading="lazy" data-fancybox="slider-item" class="img-fluid w-100">
                        </div>
                    @endif
                @endforeach



            </div>

            <!-- Navigation buttons -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
        <!-- Pagination OUTSIDE -->
        <div class="dress-pagination " style="text-align: center;"></div>
    </div>

    <div class="container my-3 d-block d-md-none">
        <div class="d-flex justify-content-center gap-2">
            <a href="#order" class="btn btn-order fs-4">
                <i class="fa-solid fa-cart-shopping me-2"></i>অর্ডার করুন
            </a>

            <a href="https://wa.me/8801622351266" target="_blank" class="btn btn-whatsapp">
                <i class="fa-brands fa-whatsapp"></i>
            </a>

            <a href="https://m.me/seldombd" target="_blank" class="btn btn-tel">
                <i class="fa-brands fa-facebook-messenger"></i>
            </a>
        </div>
    </div>
    <div class="container section">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h2 class="titel mb-4">{{ $data->materials_section->title }}</h2>
                <p class="text-center text">
                    {{ $data->materials_section->description }}
                </p>

                <div class="d-flex justify-content-center gap-2 my-2 d-none d-md-block">
                    <a href="#order" class="btn btn-order fs-4">
                        <i class="fa-solid fa-cart-shopping me-2"></i>অর্ডার করুন
                    </a>

                    <a href="https://wa.me/8801622351266" target="_blank" class="btn btn-whatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>

                    <a href="https://m.me/seldombd" target="_blank" class="btn btn-tel">
                        <i class="fa-brands fa-facebook-messenger"></i>
                    </a>
                </div>
            </div>
            <div class="col-12 col-md-6">
                @foreach ($data->materials_section->items as $material_item)
                    @if (!empty($material_item) && $material_item != null)
                        <div class="item mb-2 row justify-content-center aligin-items-center shadow-sm px-3 py-2 rounded">

                            <div class="icon col-2">
                                <i class="fa-regular fa-star text-success fs-2"></i>

                            </div>
                            <div class="text col-10">
                                <h4 class="fw-bold text-start fs-5">{{ $material_item }}</h4>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- <div class="item mb-2 row  aligin-items-center shadow-sm px-3 py-2 rounded">

                    <div class="icon col-2">
                        <i class="fa-regular fa-star text-success fs-2"></i>
                    </div>
                    <div class="text col-10">
                        <h4 class="fw-bold text-start fs-5">Salwar - Soft Silk</h4>
                    </div>
                </div>
                <div class="item mb-2 row justify-content-center aligin-items-center shadow-sm  px-3 py-2 rounded">

                    <div class="icon col-2">
                        <i class="fa-regular fa-star text-success fs-2"></i>
                    </div>
                    <div class="text col-10">
                        <h4 class="fw-bold text-start fs-5">Long - 48 Inches</h4>
                    </div>
                </div>
                <div class="item mb-2 row justify-content-center aligin-items-center shadow-sm px-3 py-2 rounded">

                    <div class="icon col-2">
                        <i class="fa-regular fa-star text-success fs-2"></i>
                    </div>
                    <div class="text col-10">
                        <h4 class="fw-bold text-start fs-5">Slives - 3 quater</h4>
                    </div>
                </div> --}}

            </div>
        </div>

        <div>

        </div>
    </div>
    <div class="container section" id="feature">
        <div class="row">
            <div class="col-md-12 mb-5">
                <h2 class="titel">{{ $data->why_us->title }}</h2>
            </div>
            @foreach ($data->why_us->items as $data_item)
                <div class="col-md-3 p-3 service-main">
                    <div class="service-box">
                        <span>

                            <i class="fa-solid fa-{{ $data_item->icon }}"></i>
                        </span>
                        <h5>{{ $data_item->title }}</h5>
                        <p>
                            {{ $data_item->text }}
                        </p>
                    </div>
                </div>
            @endforeach



        </div>
    </div>
    <div id="banner" class="py-5">
        <div class="container">
            <div class="row">
                <img src="{{ $data->promo_banner_middle->image }}" alt="Discount Offer Banner" />
            </div>
        </div>
    </div>
    <!-- testimonials -->
    <div class="container" id="review">
        <h2 class="titel mb-4">{{ $data->reviews->title }}</h2>
        <p class="text-center text">
            {{ $data->reviews->description ?? '' }}
        </p>
        <!-- Swiper container -->
        <div class="swiper myReviewSwiper">

            <div class="swiper-wrapper">
                <!-- Slide 1 -->
                @foreach ($data->reviews->items as $review_item)
                    @if ($review_item && !empty($review_item) && $review_item != null)
                        <div class="swiper-slide">
                            <img src="{{ $review_item }}" loading="lazy" class="img-fluid w-100 rounded"
                                alt="Review">
                        </div>
                    @endif
                @endforeach



                <!-- Duplicate slides as needed -->
                <!-- Slide 2, 3, etc. -->
            </div>



            <!-- Swiper navigation -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>

        </div>
        <!-- Swiper pagination -->
        <div class="testimonial-swiper-pagination" style="text-align: center;"></div>

    </div>
    <!-- testimonials end -->
    <div class="container my-3">

        <div class="d-flex justify-content-center gap-2">
            <a href="#order" class="btn btn-order">
                <i class="fa-solid fa-cart-shopping me-2"></i>অর্ডার করুন
            </a>

            <a href="https://wa.me/8801622351266" target="_blank" class="btn btn-whatsapp">
                <i class="fa-brands fa-whatsapp"></i>
            </a>

            <a href="https://m.me/seldombd" target="_blank" class="btn btn-tel">
                <i class="fa-brands fa-facebook-messenger"></i>
            </a>
        </div>
    </div>
    <!-- Qus Ans -->
    <div class="container mt-5 qus-ask" id="question">
        <div class="row">
            <h2 class="titel mb-4">{{ $data->faq->title }}</h2>
            <!-- accordion -->
            <div class="accordion mt-5" id="accordionPanelsStayOpenExample">

                <!-- FAQ 1 -->
                @foreach ($data->faq->items as $index => $faq_item)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#tab_item-{{ $index }}" aria-expanded="false"
                                aria-controls="tab_item-{{ $index }}">
                                <strong class="accordion-titel">{{ $faq_item->question }}</strong>
                            </button>
                        </h2>
                        <div id="tab_item-{{ $index }}" class="accordion-collapse collapse">
                            <div class="accordion-body">
                                <p class="accordion-text">
                                    {{ $faq_item->answer }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach


            </div>


        </div>
    </div>

    <!-- Product Pic -->
    <div class="container justify-content-center mt-5 product-gallrey" id="gallery">
        <div class="row">
            <h2 class="col-md-12 titel mt-5">{{ $data->related_gallery->title }}</h2>
        </div>
        <div class=" mt-5" id="lightgallery">
            @foreach ($data->related_gallery->items as $g_item)
                @if ($g_item && !empty($g_item) && $g_item != null)
                    <a href="{{ $g_item }}" class=" gallrey" class="item" data-fancybox="gallery-item">
                        <img class="w-100 gallrey-pic" src="{{ $g_item }}" loading="lazy" />
                    </a>
                @endif
            @endforeach


        </div>


    </div>


    {{-- Order form  --}}
    <div class="container mt-5" id="order">
        <form action="" method="POST" id="order-form">

            <div class="row gap-4">
                <h2 class="titel mt-5 mb-5">অর্ডার করুন এখনই</h2>
                <div class="col-md">
                    <h3 class="some-heading">প্রোডাক্ট নির্বাচন করুন</h3>

                    <div class="row gap-2 product-list px-3">
                        @foreach ($finalProducts as $product)
                            <div id="product-{{ $product->id }}"
                                class="p-item col-md-6 d-flex align-items-center content-site {{ $product->is_primary == 1 ? 'p-selected' : '' }}  p-2"
                                data-p-id="{{ $product->id }}" data-p-price="{{ $product->discounted_price }}"
                                data-p-weight="0" data-p-name="{{ $product->name }}">
                                <div class="me-2" style="width:100px;">

                                    <img src="{{ asset('storage/images/products/' . $product->image) }}"
                                        alt="{{ $product->name }}" class="img-fluid w-100 rounded " />
                                </div>

                                <div class="flex-column w-100">
                                    <h5 class="text-success order-name">{{ $product->name }}</h5>
                                      {{-- <p class="text-secondary"> <strong>Code:</strong>  <strong data-p-code="{{ $product?->id }}" id="product_code-{{ $product?->id }}">{{ $product?->id }}</strong></p> --}}
                                      <h6 class="" id="pitems-{{ $product->id }}">SKU:{{ $product->id }}</h6>

                                    <div class="price-box d-flex justify-content-between">
                                        <p class="select-box-price mb-auto fw-bold">
                                            @if ($product->discount_price)
                                                <del class="text-secondary text-sm">{{ $product->price }} Tk </del> -
                                            @endif

                                            <span>{{ $product->discounted_price }} Tk</span>
                                        </p>
                                      {{-- <p class=" ms-auto mt-auto fw-bold">ID: {{ $product->id }}</p>  --}}
                                    </div>
                                </div>

                            </div>
                        @endforeach


                    </div>
                    <div class="box mt-4" id="delivery-box">
                        <h3 class="some-heading mt-5">ডেলিভারী এড্রেস</h3>

                        <div class="row mb-3 mt-4">
                            <div class="col-12">
                                <label for="inputAddress" class="form-label">আপনার নাম <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="inputName" placeholder="Full Name"
                                    required name="name" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="number" class="form-label">মোবাইল নম্বর <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="inputNumber"
                                    placeholder="11 Digit Mobile No" name="phone" required />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12-md-6">
                                <label for="inputAddress" class="form-label">ঠিকানা <span
                                        class="text-danger">*</span></label>

                                <textarea class="form-control" name="address" required placeholder="Enter your Delivery Address">  </textarea>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12-md-6">
                                <div class="form-group">
                                    <label>ডেলিভারি এরিয়া <span class="text-danger">*</span></label>
                                    <div class="select-box">


                                        <select class="add form-control form-control-md" id="deliveryArea"
                                            name="delivery_area" autocomplete="off" required="">
                                            <option value="">Choose Delivery Area </option>
                                            @foreach ($delivery_areas as $index => $delivery)
                                                <option {{ $index == 0 ? 'selected' : '' }} value="{{ $delivery->id }}"
                                                    data-charge="{{ $delivery->charge }}"> {{ $delivery->name }} - TK
                                                    {{ $delivery->charge }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-md">
                    <div class="row">
                        <div class="col-md-12 order-heading">
                            <h3 class="some-heading">অর্ডার সামারি</h3>
                        </div>
                        <div id="p-cart">
                            @foreach ($selected_products as $cart_item)
                                <div id="cart-item-{{ $cart_item->id }}" class="cart-item price-info mt-4"
                                    data-p-id="{{ $cart_item->id }}" data-p-price="{{ $cart_item->discounted_price }}"
                                    data-p-weight="" data-p-name="">
                                    <div class="d-flex justify-content-between">
                                        <div>

                                            <h5 class="fw-bold">{{ $cart_item->name }}<span></span></h5>
                                      {{-- <p class="text-secondary"> <strong>{{ $cart_item?->id }}</strong>  <strong id="pcart-item-{{ $cart_item?->id }}">{{ $cart_item?->id }}</strong></p> --}}
                                      <p class="text-secondary"> <strong>{{ $cart_item?->id }}</strong></p>

                                        </div>
                                        <button type="button" class="delete-btn">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <p class="price-list"><span>৳ {{ $cart_item->discounted_price }} </span><span>
                                                x </span>
                                            <span class="quantity">1</span>
                                            <span>=</span>
                                            <span class="total-price">{{ $cart_item->discounted_price }}</span>
                                        </p>
                                        <span>
                                            <input class="id" value="{{ $cart_item->id }}" type="hidden" />
                                            <select name="quantity" class="add-quantity form-control ms-auto mt-2"
                                                autocomplete="off" required="">
                                                <option value="1" selected="selected">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                                <option value="6">6</option>
                                                <option value="7">7</option>
                                                <option value="8">8</option>
                                                <option value="9">9</option>
                                                <option value="10">10</option>
                                            </select>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="deli-price-info">
                            <div class="total d-flex justify-content-between mt-3">
                                <p class="deli-price-text">মোট</p>
                                <p class="deli-price" id="subtotal_amount" data-subtotal="0"></p>
                            </div>
                            <div class="deli-charge d-flex justify-content-between">
                                <p class="deli-price-text">ডেলিভারী চার্জ</p>
                                <p class="deli-price" id="delivery_charge_amount"
                                    data-delivery="{{ $delivery_areas->first()->charge }}">
                                    {{ $delivery_areas->first()->charge }}</p>
                            </div>
                            <div class="cashon-deli d-flex justify-content-between d-none">
                                <p class="deli-price-text">ক্যাশ অন ডেলিভারী চার্জ ১%</p>
                                <p class="deli-price" id="cod_charge_amount" data-cod="0"></p>
                            </div>
                        </div>
                        <div class="subtotal-price-info">
                            <div class="total-price d-flex justify-content-between mt-3">
                                <p class="subtotal-text">সর্বমোট</p>
                                <p class="deli-price" id="total_amount" data-total="0"></p>
                            </div>
                            <div class="deli-methode d-flex justify-content-between">
                                <p class="subtotal-text">ডেলিভারী মেথড</p>
                                <p class="subtotal-text-green">ক্যাশ অন ডেলিভারী</p>
                            </div>
                        </div>
                        <a href="index-order.html" target="_blank"> <button id="order-button"
                                class="btn mt-4 p-2 btn-order w-100">
                                <i class="fa-solid fa-bag-shopping"></i> অর্ডার প্লেস করুন
                            </button> </a>
                        <p class="i-text">
                            <i>যদি অর্ডার ইতিমধ্যেই প্লেস হয়ে থাকে, তাহলে যেকোন প্রশ্ন বা সাহায্যের জন্য আমাদের
                                <strong>WhatsApp</strong>-এ যোগাযোগ করুন।</i>
                        </p>

                    </div>
                </div>
            </div>

        </form>
    </div>


    <!-- call to action -->
    <div class="container mb-3">
        <div class="row call-to-action justify-content-center"
            style="background: url(https://i.postimg.cc/x13KHVnN/background-2.jpg) no-repeat; background-size: cover; background-position: center;">
            <div class="col-md-6">
                <h2 class="call-to-action-titel titel text-center">{{ $data->footer_cta->title }}</h2>
                <p class="call-to-action-text">

                    যেকোন জিজ্ঞাসা ও অর্ডারজনিত সমস্যায় কল করুন আমাদের হেল্পলাইনে অথবা নক করুন আমাদের হোয়াটসঅ্যাপ বা ফেসবুক
                    পেজে। আমরা আছি সকাল ১০ টা থেকে রাত ৮ টা পর্যন্ত আপনার সেবায়।
                </p>
            </div>
            <div class="d-flex gap-2 justify-content-center mb-5">
                <a href="tel:+8801622-351266" class="btn btn-order">
                    <i class="fa-solid fa-phone me-2"></i>হেল্পলাইন
                </a>
                <a href="https://wa.me/8801622351266" class="btn btn-whatsapp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
                <a href="https://m.me/seldombd" class="btn btn-tel">
                    <i class="fa-brands fa-facebook-messenger"></i>
                </a>
            </div>
        </div>
    </div>
     @if ($related_products->count() > 0)

            <div class="container sec-style-2">


                <div class="sec-header">
                    <h2 class="sec-title text-primary-color">More Products - আরো দেখুন</h2>
                    <hr class="divider mt-0 text-primary-color bg-primary-color " style="height: 2px;">
                </div>
                <div class="sec-body">
                    <div class="sec-grid-box">
                        @foreach ($related_products as $pitem)
                            <div class="sec-grid-item p-card-1">

                                <div class="p-img-box">
                                    <a
                                        href="{{ $pitem?->url }}">
                                        <img loading="lazy" src="{{ asset('storage/images/products/' . $pitem->image) }}"
                                            alt="">
                                    </a>
                                </div>
                                <div class="p-info">
                                    <div class="prices">
                                        @if ($pitem->discount_price && $pitem->discount_price > 0)
                                            <del class="old-price">৳ {{ $pitem->price }}</del>
                                            <span class="price">৳ {{ $pitem->discount_price }}</span>
                                        @else
                                            <span class="price">Price: ৳ {{ $pitem->price }}</span>
                                        @endif
                                    </div>
                                    <a
                                        href="{{ $pitem?->url }}">

                                        <h1 class="p-title">{{ $pitem->name }}</h1>
                                    </a>
                                    <a
                                        href="{{ $pitem?->url }}">
                                        <p class="p-description">
                                            বিস্তারিত দেখুন
                                        </p>
                                    </a>
                                </div>
                                <div class="p-btn-group">
                                    <a class="btn btn-primary w-100 d-block"
                                        href="{{ $pitem?->url }}">Buy
                                        Now</a>
                                </div>


                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

    @endif
@endsection
@push('scripts')
    <!-- external js -->
    <script>
        var swiper = new Swiper(".myReviewSwiper", {
            spaceBetween: 30,
            grabCursor: true,
            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
            },
            loop: true,
            // Pagination
            pagination: {
                el: ".testimonial-swiper-pagination",
                clickable: true,
            },
            // Next and previous navigation
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            // Responsive breakpoints
            breakpoints: {
                0: {
                    slidesPerView: 2
                },
                768: {
                    slidesPerView: 2
                },
                1024: {
                    slidesPerView: 4
                }
            }
        });
    </script>
    @if (session('status') == 'error')
        <script>
            Swal.fire({
                icon: "{{ session('status') == 'error' ? 'error' : 'success' }}",
                title: "{{ session('status') == 'error' ? 'দুঃখিত!' : 'সফল!' }}",
                text: "{{ session('message') }}",
                confirmButtonText: 'ঠিক আছে',
                timer: 4000, // Auto close after 4 seconds
                timerProgressBar: true,
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const player = new Plyr('#player', {
                ratio: '9:16',
                controls: [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'mute',
                    'volume',
                    'fullscreen'
                ]
            });
        });
    </script>

    <script>
        $(document).ready(function() {


            var pCart = $("#p-cart");

            function updateTotal() {
                var cartItems = $('.cart-item');
                var subtotal = $("#subtotal_amount").data("subtotal");
                var total_amount = $("#total_amount").data("total");
                var delivery_charge = $("#delivery_charge_amount").data("delivery");
                var cod_charge_percent = $("#cod_charge_amount").data("cod");

                var calc_subtotal = 0;
                var obj = {
                    cartItems: []
                };

                cartItems.each(function() {


                    var price = $(this).data("p-price");
                    var weight = $(this).data("p-weight");
                    var name = $(this).data("p-name");
                    var quantity = $(this).find(".add-quantity :selected").val();
                    var id = $(this).data("p-id");
                    var ptotal = price * quantity;

                    calc_subtotal += ptotal;
                    console.log(calc_subtotal, ptotal, price, quantity, id);

                    obj.cartItems.push({
                        id: id,
                        name: name,
                        quantity: quantity,
                        price: price,
                        weight: weight
                    });


                })




                var total = calc_subtotal + delivery_charge;
                var cod_charge_amount = (total * cod_charge_percent) / 100;
                total = total + cod_charge_amount;
                obj.subtotal = calc_subtotal;
                obj.delivery_charge = delivery_charge;
                obj.cod = cod_charge_percent;
                obj.total = total;
                obj._token = $('meta[name="csrf-token"]').attr("content");

                $("#subtotal_amount").text(Math.floor(calc_subtotal));
                $("#total_amount").text(Math.floor(total));
                $("#cod_charge_amount").text(Math.floor(cod_charge_amount));

                return obj;
            }
            $('#order-form').on('submit', async function(e) {
                e.preventDefault();
                if (!$("#order-form")[0].checkValidity()) {

                }
                swal.fire({
                    title: "Loading...",
                    html: `
                        <div class="d-flex justify-content-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });

                // sentInitialCheckout()
                var name = $("input[name='name']").val();
                var phone = $("input[name='phone']").val();
                var address = $("textarea[name='address']").val();
                var size = $("select[name='size").val();
                var product_id = $("input[name='product_id").val();
                var quantity = $("input[name='quantity").val();
                var delivery_area = $("select[name='delivery_area").val();
                var token = "{{ csrf_token() }}";
                let orderData = updateTotal();
                // console.log(orderData);

                var order_data = {
                    name: name,
                    phone: phone,
                    address: address,
                    size: size,
                    product_id: product_id,
                    quantity: quantity,
                    delivery_area: delivery_area,
                    orderCart: orderData,
                    XSRF_TOKEN: token,

                }
                console.log(order_data);
                fetch('{{ route('cart.order.purchase') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(order_data)
                }).then(response => response.json()).then(data => {
                    console.log('Order Now tracked:', data);
                    setTimeout(() => {

                    }, 2000);
                    if (data.status == 'error') {


                        Swal.fire({
                            icon: data.status,
                            title: data.status === 'error' ? 'দুঃখিত!' : 'সফল!',
                            text: data.message,
                            confirmButtonText: 'ঠিক আছে',
                            // timer: 4000, // Auto close after 4 seconds
                            timerProgressBar: true,
                        });
                    } else if (data.status == 'success') {

                        Swal.fire({
                            icon: data.status,
                            title: data.status === 'error' ? 'দুঃখিত!' : 'সফল!',
                            text: data.message,
                            confirmButtonText: 'ঠিক আছে',
                            timer: 4000, // Auto close after 4 seconds
                            timerProgressBar: true,
                        });
                        window.location.href = data.redirect_url;
                    }
                }).catch(error => {
                    console.error('Error tracking Facebook Pixel ViewContent:', error);
                });


            });

            // Attach a click event to each `.p-item`
            $(".p-item").on('click', function() {
                // Toggle the 'p-selected' class
                $(this).toggleClass('p-selected');

                // Retrieve data attributes
                var price = $(this).data("p-price"); // No need for `data-` prefix in `.data()`
                var weight = $(this).data("p-weight");
                var name = $(this).data("p-name");
                var id = $(this).data("p-id");
                var code = $(this).data("p-code");

                // Log the values (optional)
                console.log("Price:", price, "Weight:", weight, "Name:", name, "ID:", id);

                // Add the item to the cart
                if ($(pCart).find("#cart-item-" + id + "").length == 0) {
                    pCart.append(`<div id="cart-item-${id}" class="cart-item price-info mt-4" data-p-id="${id}" data-p-price="${price}" data-p-weight="${weight}" data-p-name="${name}" >
                        <div class="d-flex justify-content-between">
                            <div>
                            <h5 class="fw-bold">${name} </h5>
                            <!--<p class="text-secondary"> <strong>Code:</strong>  <strong id="pcart-item-${id}">${id}</strong></p>-->
                            <p class="text-secondary"> <strong>${id}</strong></p>

                            </div>
                            <button type="button" class="delete-btn" >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p class="price-list"><span>৳ ${price} </span><span> x </span> <span class="quantity">১</span> <span>=</span>
                                <span class="total-price">${price}</span>
                            </p>
                            <span>

                                <input class="id" value="${id}" type="hidden" />
                                <select name="quantity" class="add-quantity form-control ms-auto mt-2"
                                    autocomplete="off" required="">
                                    <option value="1" selected="selected">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>

                            </span>
                        </div>
                    </div>
            `);
                } else {
                    $(pCart).find("#cart-item-" + id + "").remove();
                }

                updateTotal();
            });

            // Attach a click event to each `.delete-btn`

            // Use event delegation to handle dynamically added elements
            $(document).on("click", ".delete-btn", function() {
                console.log("Delete button clicked");

                // Get the cart item ID using .closest()
                var id = $(this).closest('.cart-item').data("p-id");
                console.log("Item ID to remove:", id);
                if ($(".product-list").find("#product-" + id + "").hasClass("p-selected")) {
                    $(".product-list").find("#product-" + id + "").removeClass("p-selected");
                }

                // Remove the item from the cart
                $(this).closest('.cart-item').remove();

                // Update the cart total
                updateTotal();
            });
            $("#deliveryArea").on("change", function() {
                var id = $(this).find(":selected").val();
                var id = $(this).find(":selected").val();
                var charge = $(this).find(":selected").data("charge");
                $("#delivery_charge_amount").data("delivery", charge);
                $("#delivery_charge_amount").text(charge.toFixed(2) + " TK");
                updateTotal();
            })

            $(document).on("change", ".add-quantity", function() {
                updateTotal();
            });
            updateTotal();

        });
        Fancybox.bind('[data-fancybox="gallery-item"]', {
            // Your custom options for a specific gallery
        });
         Fancybox.bind('[data-fancybox="slider-item"]', {
            // Your custom options for a specific gallery
        });
    </script>
    <script>
        const mySwiper = new Swiper(".dressSwiper", {

            loop: true,
            spaceBetween: 20,

            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
            },

            pagination: {
                el: ".dress-pagination",
                clickable: true,
            },

            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },

            breakpoints: {
                0: {
                    slidesPerView: 2,
                },
                768: {
                    slidesPerView: 3,
                }
            }

        });
    </script>




    <script>
        let is_sent = false;

        $(document).ready(function() {
            var custom_fbc = localStorage.getItem('custom_fbc') ?? '';
            console.log('custom_fbc:', custom_fbc);

            let current_url = "{{ url()->current() }}";
            console.log('current_url:', current_url);
            let referrer = "{{ url()->previous() }}";
            console.log('referrer:', referrer);
            let event_id = new Date().getTime() + '_' + Math.random().toString(36).substring(2, 9);
            let initiatecheckout_event_id = new Date().getTime() + '_' + Math.random().toString(36).substring(2, 9);

            console.log("Event_ID: " + event_id);
            var user_data = document.cookie.split(';').filter(item => item.trim().startsWith('_sfud='))[0]?.split(
                '=')[1];
            user_data = user_data ? JSON.parse(decodeURIComponent(user_data)) : null;
            var device_id = document.cookie.split(';').filter(item => item.trim().startsWith('_sfdid='))[0]?.split(
                '=')[1];
            console.log('user_data', user_data);
            console.log('device_id', device_id);

            var ip_address = '{{ request()->ip() }}';
            console.log('IP Address:', ip_address);
            var userAgent = navigator.userAgent;
            console.log('User Agent:', userAgent);
            var token = "{{ csrf_token() }}";


            let pamount = "{{ $product->discounted_price }}";
            pamount = parseFloat(pamount);
            console.log('dom ready');
            var viewContentDataSet = {

                event: 'ViewContent',
                event_id: event_id,
                event_source_url: current_url,
                referrer_url: referrer_url,
                ecommerce: {
                    value: pamount, // Number, two decimals, required
                    currency: 'BDT', // String, required
                    items: [{
                        item_name: "{{ $product->name ?? null }}", // String, required
                        item_id: "{{ $product->id ?? null }}", // String, required
                        price: pamount, // Number, two decimals, required
                        quantity: 1, // Integer, required
                        item_category: "Women's Clothing", // String, optional but advised if available
                        item_brand: 'Seldom', // String, optional, might be useful if you sell different brands
                        item_variant: null // String, optional
                    }]
                },

                user_data: {
                    first_name: user_data?.first_name ?? null,
                    last_name: user_data?.last_name ?? null,
                    // email_address: null,
                    phone_number: user_data?.phone ?? null,
                    street: user_data?.street ?? null,
                    country: "BD",
                    state: user_data?.state ?? null,
                    city: user_data?.city ?? null,
                    // region: null,
                    zipcode: user_data?.zip_code ?? null,
                    customer_id: user_data?.id ||
                        device_id || null,
                    // new_customer: 'true' // এটি অনুমান করা কঠিন হবে
                    client_ip_address: ip_address || null,
                    client_user_agent: userAgent || null,
                    fbc: custom_fbc,
                }
            }

            dataLayer = window.dataLayer || [];
            dataLayer.push({
                ecommerce: null
            }); // we want to null out the ecommerce object, so there's no overlap if events happen on the same page



            function sentInitialCheckout() {
                let value = parseFloat($("#total").text());
                let quantity = parseFloat($(".quantity-field").val());
                let name = $("input[name='name").val();
                let phone = $("input[name='phone").val();
                let address = $("input[name='address").val();
                let size = $("select[name='size").val();

                console.log(value);
                var initiateCheckoutDataSet = {
                    event: 'begin_checkout',
                    event_id: initiatecheckout_event_id,
                    event_source_url: current_url,
                    referrer_url: referrer_url,
                    ecommerce: {
                        value: value, // Number, two decimals, required
                        currency: 'BDT', // String, required
                        items: [{
                            item_name: "{{ $product->name ?? null }}", // String, required
                            item_id: "{{ $product->id ?? null }}", // String, required
                            price: pamount, // Number, two decimals, required
                            quantity: quantity, // Integer, required
                            item_category: "Pants", // String, optional but advised if available
                            item_brand: 'YoungStar Life', // String, optional, might be useful if you sell different brands
                            item_variant: size // String, optional
                        }]
                    },
                    // user_data অবজেক্টে শুধুমাত্র সেই ডেটা রাখুন যা আপনার কাছে উপলব্ধ
                    // অথবা, যদি কোনো ইউজার ডেটা না থাকে, তাহলে এই অংশটি বাদ দিন।
                    // উদাহরণস্বরূপ, যদি আপনি একটি সেশন আইডি ট্র্যাক করতে পারেন:
                    user_data: {
                        first_name: name || user_data?.first_name || null, // বা এই লাইনগুলো বাদ দিন
                        last_name: user_data?.last_name || null,
                        // email_address: null,
                        phone_number: phone || user_data?.phone || null,
                        street: user_data?.street || address || null,
                        country: "BD", // IP Address থেকে পাওয়া গেলে
                        city: user_data?.city || null,
                        // region: null,
                        zipcode: user_data?.zip_code || null,
                        customer_id: user_data?.id ||
                            device_id || null, // উদাহরণ: সেশন স্টোরেজ থেকে visitorId ব্যবহার করা
                        // new_customer: 'true' // এটি অনুমান করা কঠিন হবে
                        client_ip_address: ip_address || null,
                        client_user_agent: userAgent || null,

                    }
                }

                dataLayer.push(initiateCheckoutDataSet);

                let result;
                fetch("/fb-pixel-capi?event_name=initiate_checkout&segment=" + segment, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify(initiateCheckoutDataSet)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Facebook Pixel InitiateCheckout tracked:', data);
                        result = data;
                        is_sent = true;
                    })
                    .catch(error => {
                        console.error('Error tracking Facebook Pixel InitiateCheckout:', error);
                    });
                return result;

            }
            $('#order-button').on('click', async function(e) {
                // e.preventDefault();

                sentInitialCheckout();

            });



            dataLayer.push(viewContentDataSet);
            fetch("/fb-pixel-capi?event_name=view_content&segment=" + segment, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(viewContentDataSet)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Facebook Pixel ViewContent tracked:', data);
                })
                .catch(error => {
                    console.error('Error tracking Facebook Pixel ViewContent:', error);
                });

        });
    </script>


@endpush
