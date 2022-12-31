-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Czas generowania: 31 Gru 2022, 11:58
-- Wersja serwera: 10.5.12-MariaDB-0+deb11u1
-- Wersja PHP: 7.4.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Baza danych: `serwer16603_portal`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `blocked_users_switch`
--

CREATE TABLE `blocked_users_switch` (
  `id` int(11) NOT NULL,
  `id_user_a` int(11) NOT NULL COMMENT 'User blokujacy',
  `id_user_b` int(11) NOT NULL COMMENT 'User blokowany',
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `id_user_a` int(255) NOT NULL,
  `id_user_b` int(255) NOT NULL,
  `conversation` int(255) NOT NULL,
  `position` int(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `id_author` int(11) NOT NULL,
  `nick_show` int(11) NOT NULL DEFAULT 1,
  `creation_date` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `background` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `groups_switch`
--

CREATE TABLE `groups_switch` (
  `id` int(11) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `group_url` varchar(255) NOT NULL,
  `id_group` int(11) NOT NULL,
  `id_post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `group_delete_post_notification`
--

CREATE TABLE `group_delete_post_notification` (
  `id` int(11) NOT NULL,
  `id_user_a` int(11) NOT NULL COMMENT 'Właściciel grupy',
  `id_user_b` int(11) NOT NULL COMMENT 'User blokowany',
  `id_post` int(11) NOT NULL,
  `id_group` int(11) NOT NULL,
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `group_user_watching`
--

CREATE TABLE `group_user_watching` (
  `id` int(11) NOT NULL,
  `id_group` int(11) NOT NULL,
  `id_user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `hashtag_switch`
--

CREATE TABLE `hashtag_switch` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(255) NOT NULL,
  `tag_url` varchar(255) NOT NULL,
  `id_post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation` int(255) NOT NULL,
  `id_user` int(255) NOT NULL,
  `msg` blob NOT NULL,
  `date` varchar(100) NOT NULL,
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `id_author` int(11) NOT NULL,
  `title` varchar(300) CHARACTER SET utf8mb4 NOT NULL,
  `post_url` varchar(255) NOT NULL,
  `text` text CHARACTER SET utf8mb4 DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
  `url_video` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `url_photos` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `date_added` varchar(10) CHARACTER SET utf8mb4 NOT NULL,
  `time_added` varchar(10) CHARACTER SET utf8mb4 NOT NULL,
  `number_likes` int(11) NOT NULL DEFAULT 0 COMMENT 'Suma "+"',
  `number_dislikes` int(11) NOT NULL DEFAULT 0 COMMENT 'Suma "-"',
  `spoiler` int(11) NOT NULL DEFAULT 0,
  `nsfw` int(11) NOT NULL DEFAULT 0,
  `id_group` int(11) NOT NULL DEFAULT 0,
  `availability` int(11) NOT NULL DEFAULT 0 COMMENT 'Stopien dostepnosci postu dla userow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `posts_comments`
--

CREATE TABLE `posts_comments` (
  `id` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `id_author` int(11) NOT NULL,
  `date` varchar(255) NOT NULL,
  `id_parent` varchar(255) NOT NULL DEFAULT 'NULL',
  `id_parent2` varchar(255) NOT NULL DEFAULT 'NULL',
  `content` text NOT NULL,
  `status_notification1` int(11) NOT NULL DEFAULT 0,
  `status_notification2` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rating_comment_switch`
--

CREATE TABLE `rating_comment_switch` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_comment` int(11) NOT NULL,
  `rating` int(11) NOT NULL COMMENT '0 - / 1 +',
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `rating_post_switch`
--

CREATE TABLE `rating_post_switch` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_post` int(11) NOT NULL,
  `rating` int(11) NOT NULL COMMENT '0 - / 1 +',
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `report_user`
--

CREATE TABLE `report_user` (
  `id` int(11) NOT NULL,
  `id_user_a` int(11) NOT NULL COMMENT 'User zgłaszający',
  `id_user_b` int(11) NOT NULL COMMENT 'User zgłoszony',
  `id_post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL COMMENT 'Jakiego użytkownika dotyczą ustawienia',
  `email_notifications` int(11) NOT NULL DEFAULT 0 COMMENT 'Powiadomienia na e-mail (0-off / 1-on)',
  `dark_theme` int(11) NOT NULL DEFAULT 1 COMMENT 'Włącz ciemny motyw (0-off / 1-on)',
  `spoiler` int(11) NOT NULL DEFAULT 0,
  `nsfw` int(11) NOT NULL DEFAULT 0,
  `nick_show` int(11) NOT NULL DEFAULT 0,
  `availability` int(11) NOT NULL DEFAULT 0 COMMENT 'Publikowany wpis ma być z góry (0-publiczny / 1-tylko obserwatorzy / 2-prywatny)',
  `account_type` int(11) NOT NULL DEFAULT 0 COMMENT 'Konto należy do (0-osoba prywatna / 1-osoba publiczna / 2-firma)',
  `user_description` text DEFAULT NULL COMMENT 'Kilka słów o sobie',
  `avatar` varchar(255) DEFAULT 'null' COMMENT 'Zakodowany link do folderu użytkownika z awatarem',
  `background` varchar(255) NOT NULL DEFAULT 'null'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `unique_id` int(255) NOT NULL,
  `nick` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `sex` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL,
  `facebook_access_token` varchar(255) DEFAULT NULL,
  `roles` longtext NOT NULL COMMENT '(DC2Type:json)',
  `google_id` varchar(255) DEFAULT NULL,
  `google_access_token` varchar(255) DEFAULT NULL,
  `date_joining` varchar(255) NOT NULL,
  `date_deletion` varchar(255) DEFAULT NULL,
  `activated` int(11) NOT NULL DEFAULT 0,
  `ban` varchar(255) NOT NULL DEFAULT 'null' COMMENT 'czasowy: Y-m-d / na stałe: 1',
  `verification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_pass_reset`
--

CREATE TABLE `user_pass_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(255) CHARACTER SET latin1 NOT NULL,
  `data` varchar(255) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `user_token`
--

CREATE TABLE `user_token` (
  `id` int(11) NOT NULL,
  `email` varchar(255) CHARACTER SET latin1 NOT NULL,
  `token` text CHARACTER SET latin1 NOT NULL,
  `data` varchar(255) CHARACTER SET latin1 NOT NULL,
  `active` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `watching_users_switch`
--

CREATE TABLE `watching_users_switch` (
  `id` int(11) NOT NULL,
  `id_user_a` int(11) NOT NULL COMMENT 'obserwujący',
  `id_user_b` int(11) NOT NULL COMMENT 'obserwowany',
  `status_notification` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `blocked_users_switch`
--
ALTER TABLE `blocked_users_switch`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `groups_switch`
--
ALTER TABLE `groups_switch`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `group_delete_post_notification`
--
ALTER TABLE `group_delete_post_notification`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `group_user_watching`
--
ALTER TABLE `group_user_watching`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `hashtag_switch`
--
ALTER TABLE `hashtag_switch`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `posts_comments`
--
ALTER TABLE `posts_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `rating_comment_switch`
--
ALTER TABLE `rating_comment_switch`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `rating_post_switch`
--
ALTER TABLE `rating_post_switch`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `report_user`
--
ALTER TABLE `report_user`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`);

--
-- Indeksy dla tabeli `user_pass_reset`
--
ALTER TABLE `user_pass_reset`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `user_token`
--
ALTER TABLE `user_token`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `watching_users_switch`
--
ALTER TABLE `watching_users_switch`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT dla tabel zrzutów
--

--
-- AUTO_INCREMENT dla tabeli `blocked_users_switch`
--
ALTER TABLE `blocked_users_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `groups_switch`
--
ALTER TABLE `groups_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `group_delete_post_notification`
--
ALTER TABLE `group_delete_post_notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `group_user_watching`
--
ALTER TABLE `group_user_watching`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `hashtag_switch`
--
ALTER TABLE `hashtag_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `posts_comments`
--
ALTER TABLE `posts_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `rating_comment_switch`
--
ALTER TABLE `rating_comment_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `rating_post_switch`
--
ALTER TABLE `rating_post_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `report_user`
--
ALTER TABLE `report_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `user_pass_reset`
--
ALTER TABLE `user_pass_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `user_token`
--
ALTER TABLE `user_token`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT dla tabeli `watching_users_switch`
--
ALTER TABLE `watching_users_switch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
